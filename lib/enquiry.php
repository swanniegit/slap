<?php
/**
 * The enquiry form: one field declaration that drives both the markup and the
 * validation, plus storage and notification.
 *
 * The mockup form had no handler at all — it flipped a flag and said "Sounds
 * like a plan!" whether or not anything had been sent. That is the one failure
 * this file exists to prevent, so the order is deliberate: an enquiry is
 * written to disk FIRST and emailed SECOND. Storage is the record; email is
 * only the notification. If SMTP is misconfigured or down, the enquiry is still
 * on the volume and can be read back — nothing is ever silently dropped.
 */
declare(strict_types=1);

/**
 * Every field, once. partials/enquiry-form.php renders this and
 * slap_enquiry_validate() checks it, so a field cannot exist in the form
 * without being validated, or be validated without appearing in the form.
 *
 * 'label' is what the form control says; 'discloses' is what the privacy page
 * calls it. They are different jobs: a label is a prompt ("Tell us about it")
 * while a disclosure has to name a category of personal information. Reusing
 * the label made the privacy page list two questions instead of two things.
 */
function slap_enquiry_fields(): array
{
    return [
        'name' => [
            'label'        => 'Your name',
            'discloses'    => 'Your name',
            'type'         => 'text',
            'required'     => true,
            'autocomplete' => 'name',
            'max'          => 80,
        ],
        'email' => [
            'label'        => 'Email',
            'discloses'    => 'Your email address',
            'type'         => 'email',
            'required'     => true,
            'autocomplete' => 'email',
            'max'          => 120,
        ],
        'whatsapp' => [
            'label'        => 'WhatsApp number',
            'discloses'    => 'Your WhatsApp number',
            'type'         => 'tel',
            'required'     => false,
            'autocomplete' => 'tel',
            'hint'         => 'Optional. Quicker than email if you want to send photos.',
            'max'          => 30,
        ],
        'kind' => [
            'label'     => 'What would you like made?',
            'discloses' => 'Which kind of bear you are asking about',
            'type'     => 'radio',
            'required' => true,
            'default'  => 'memory',
            'options'  => [
                'memory'    => 'A memory bear, from my fabric',
                'character' => 'A character bear, made new',
                'unsure'    => 'Not sure yet',
            ],
        ],
        'message' => [
            'label'     => 'Tell us about it',
            'discloses' => 'Whatever you write in the message box',
            'type'     => 'textarea',
            'required' => true,
            'rows'     => 6,
            'hint'     => 'What the fabric is, who the bear is for, and when you need it.',
            'max'      => 4000,
        ],
    ];
}

/** The hidden field a bot fills in. Meaningless on purpose — see the note below. */
const SLAP_TRAP_FIELD = 'slap_ref';

/** The hidden field carrying when the form was rendered. */
const SLAP_TIME_FIELD = 'slap_t';

/**
 * Validate a submission.
 *
 * Returns ['values' => cleaned strings keyed by field, 'errors' => messages
 * keyed by field, 'drop' => true when the submission is a bot].
 *
 * Bot submissions are reported to the caller as a drop rather than an error:
 * telling a script exactly which check caught it is how the next version gets
 * past it. A real person can never trip either check — the trap field is hidden
 * and unlabelled, and nobody fills a five-field form in under four seconds.
 *
 * The trap field is named "slap_ref" rather than anything meaningful. A honeypot
 * called "company_website" or "address" gets filled in by browser autofill, and
 * then a real enquiry is discarded behind a success message with no bounce and
 * no error — invisible until someone phones to ask why nobody replied.
 */
function slap_enquiry_validate(array $post): array
{
    if (trim((string)($post[SLAP_TRAP_FIELD] ?? '')) !== '') {
        return ['values' => [], 'errors' => [], 'drop' => 'trap'];
    }

    $rendered = (int)($post[SLAP_TIME_FIELD] ?? 0);
    if ($rendered <= 0 || (time() - $rendered) < SLAP_MIN_FILL_SECONDS) {
        return ['values' => [], 'errors' => [], 'drop' => 'too-fast'];
    }

    $values = [];
    $errors = [];

    foreach (slap_enquiry_fields() as $key => $f) {
        $raw = $post[$key] ?? '';
        $raw = is_string($raw) ? trim($raw) : '';

        // Strip control characters: they are never typed and they corrupt both
        // the JSONL record and the email headers.
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw) ?? '';

        if (isset($f['max']) && mb_strlen($raw) > $f['max']) {
            $raw = mb_substr($raw, 0, $f['max']);
        }

        if ($f['type'] === 'radio' && !isset($f['options'][$raw])) {
            $raw = $f['default'] ?? '';
        }

        $values[$key] = $raw;

        if (!empty($f['required']) && $raw === '') {
            $errors[$key] = slap_enquiry_missing_message($key);
        }
    }

    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'That email address is missing something. Check it and send again.';
    }

    if ($values['message'] !== '' && mb_strlen($values['message']) < 10) {
        $errors['message'] = 'Add a sentence or two so we know what we are quoting on.';
    }

    return ['values' => $values, 'errors' => $errors, 'drop' => null];
}

/**
 * What to say when a required field is empty. Each one names the field and what
 * it is for, because "This field is required" tells someone nothing they cannot
 * already see.
 */
function slap_enquiry_missing_message(string $key): string
{
    return match ($key) {
        'name'    => 'Add your name so we know who we are replying to.',
        'email'   => 'Add an email address — it is where the quote goes.',
        'message' => 'Tell us what you have in mind, even just a sentence.',
        default   => 'This one is needed before we can send the enquiry.',
    };
}

/**
 * Append the enquiry to the log on the data volume.
 *
 * JSON Lines rather than a database: one enquiry is one line, the file is
 * readable with `tail`, an append cannot corrupt the lines before it, and there
 * is no schema to migrate. LOCK_EX because two people can submit at once.
 *
 * Returns false if the write failed, which the caller must treat as a failed
 * submission — telling someone their enquiry arrived when it did not is the
 * exact bug this file was written to avoid.
 */
function slap_enquiry_store(array $values): bool
{
    $path = SLAP_ENQUIRY_LOG;
    $dir  = dirname($path);

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        error_log('slap: enquiry log directory is not writable: ' . $dir);
        return false;
    }

    $line = json_encode(
        ['received' => gmdate('c')] + $values,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($line === false) {
        error_log('slap: enquiry could not be encoded');
        return false;
    }

    return @file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Email the enquiry, if SMTP is configured.
 *
 * Returns false when it could not be sent, including when it was never
 * configured. The caller does NOT treat that as a failed submission: the
 * enquiry is already on disk. It is logged so the gap is visible in the
 * container logs rather than being discovered by a customer.
 *
 * Configuration is by environment variable so no credential is ever committed:
 *   SLAP_SMTP_HOST, SLAP_SMTP_PORT, SLAP_SMTP_USER, SLAP_SMTP_PASS,
 *   SLAP_MAIL_FROM, SLAP_MAIL_TO
 */
function slap_enquiry_notify(array $values): bool
{
    $cfg = [
        'host' => getenv('SLAP_SMTP_HOST') ?: '',
        'port' => (int)(getenv('SLAP_SMTP_PORT') ?: 587),
        'user' => getenv('SLAP_SMTP_USER') ?: '',
        'pass' => getenv('SLAP_SMTP_PASS') ?: '',
        'from' => getenv('SLAP_MAIL_FROM') ?: '',
        'to'   => getenv('SLAP_MAIL_TO') ?: '',
    ];

    if ($cfg['host'] === '' || $cfg['to'] === '' || $cfg['from'] === '') {
        error_log('slap: enquiry stored but not emailed — SMTP is not configured');
        return false;
    }

    $fields = slap_enquiry_fields();
    $lines  = [];
    foreach ($values as $key => $value) {
        if ($value === '') {
            continue;
        }
        $label   = $fields[$key]['label'] ?? $key;
        $display = $fields[$key]['options'][$value] ?? $value;
        $lines[] = $label . ': ' . $display;
    }

    // Loaded here rather than in lib/bootstrap.php: this is the only code path
    // in the whole site that needs Composer, and every other page — and CI's
    // lint pass — stays independent of whether vendor/ has been installed.
    $autoload = SLAP_ROOT . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('slap: enquiry stored but not emailed — vendor/autoload.php is missing');
        return false;
    }
    require_once $autoload;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = $cfg['port'];
        $mail->SMTPAuth   = $cfg['user'] !== '';
        $mail->Username   = $cfg['user'];
        $mail->Password   = $cfg['pass'];
        $mail->SMTPSecure = $cfg['port'] === 465
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from'], SLAP_ORG['name'] . ' website');
        $mail->addAddress($cfg['to']);

        // Reply-To, never From: sending as the visitor's address fails SPF and
        // lands the notification in spam, which looks exactly like a form that
        // does not work.
        if ($values['email'] !== '') {
            $mail->addReplyTo($values['email'], $values['name'] ?: 'Website enquiry');
        }

        $mail->Subject = 'Enquiry from ' . ($values['name'] ?: 'the website');
        $mail->Body    = implode("\n\n", $lines);

        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('slap: enquiry stored but email failed — ' . $e->getMessage());
        return false;
    }
}
