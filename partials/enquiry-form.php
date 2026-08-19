<?php
/**
 * The enquiry form, rendered from slap_enquiry_fields().
 *
 * Every input, its label, its hint and its validation come from that one
 * declaration, so a field cannot appear here without being checked on the
 * server — and the mockup's three copy-pasted <input style="…"> blocks, which
 * had already drifted to two different border colours, collapse to one loop.
 *
 * @var array $values submitted values, empty on a fresh render
 * @var array $errors messages keyed by field name
 */
declare(strict_types=1);

$fields = slap_enquiry_fields();
$values = $values ?? [];
$errors = $errors ?? [];
?>
<form class="form panel-stitched" method="post" action="/enquiry/" novalidate>

  <?php if ($errors): ?>
    <div class="form__summary" role="alert" tabindex="-1" id="form-errors">
      <h2 class="form__summary-title">Nothing was sent yet</h2>
      <ul class="form__summary-list">
        <?php foreach ($errors as $key => $message): ?>
          <li><a href="#field-<?= slap_e($key) ?>"><?= slap_e($message) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php foreach ($fields as $key => $f):
      $id       = 'field-' . $key;
      $hintId   = $id . '-hint';
      $errorId  = $id . '-error';
      $hasError = isset($errors[$key]);
      $describe = implode(' ', array_filter([
          !empty($f['hint']) ? $hintId : null,
          $hasError ? $errorId : null,
      ]));
      $value    = $values[$key] ?? ($f['default'] ?? '');
  ?>

    <?php /* The fieldset, not the wrapper div, carries aria-describedby: a radio
             group's accessible description belongs to the group, and the group
             is the fieldset. The error paragraph is rendered here too. It cannot
             fire today — slap_enquiry_validate() falls back to the field's
             'default' for any radio, so 'kind' is never empty — but the error
             summary already links to #field-kind, and a group that could be
             named in the summary with nothing to show at the field itself is an
             error signalled by absence. */ ?>
    <?php if ($f['type'] === 'radio'): ?>
      <fieldset class="field field--choice<?= $hasError ? ' field--error' : '' ?>"
                <?= $hasError ? 'aria-invalid="true"' : '' ?>
                <?= $describe ? 'aria-describedby="' . slap_e($describe) . '"' : '' ?>>
        <legend class="field__label"><?= slap_e($f['label']) ?></legend>
        <div class="field__choices" id="<?= slap_e($id) ?>" tabindex="-1">
          <?php foreach ($f['options'] as $optValue => $optLabel): ?>
            <label class="choice">
              <input class="choice__input" type="radio" name="<?= slap_e($key) ?>"
                     value="<?= slap_e($optValue) ?>"
                     <?= $value === $optValue ? 'checked' : '' ?>>
              <span class="choice__text"><?= slap_e($optLabel) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if ($hasError): ?>
          <p class="field__error" id="<?= slap_e($errorId) ?>"><?= slap_e($errors[$key]) ?></p>
        <?php endif; ?>
      </fieldset>

    <?php else: ?>
      <div class="field<?= $hasError ? ' field--error' : '' ?>">
        <label class="field__label" for="<?= slap_e($id) ?>">
          <?= slap_e($f['label']) ?><?php if (empty($f['required'])): ?><span class="field__optional"> (optional)</span><?php endif; ?>
        </label>

        <?php if (!empty($f['hint'])): ?>
          <p class="field__hint" id="<?= slap_e($hintId) ?>"><?= slap_e($f['hint']) ?></p>
        <?php endif; ?>

        <?php if ($f['type'] === 'textarea'): ?>
          <textarea class="field__input" id="<?= slap_e($id) ?>" name="<?= slap_e($key) ?>"
                    rows="<?= (int)($f['rows'] ?? 5) ?>" maxlength="<?= (int)$f['max'] ?>"
                    <?= !empty($f['required']) ? 'required' : '' ?>
                    <?= $hasError ? 'aria-invalid="true"' : '' ?>
                    <?= $describe ? 'aria-describedby="' . slap_e($describe) . '"' : '' ?>><?= slap_e($value) ?></textarea>
        <?php else: ?>
          <input class="field__input" id="<?= slap_e($id) ?>" name="<?= slap_e($key) ?>"
                 type="<?= slap_e($f['type']) ?>" value="<?= slap_e($value) ?>"
                 maxlength="<?= (int)$f['max'] ?>"
                 <?= !empty($f['autocomplete']) ? 'autocomplete="' . slap_e($f['autocomplete']) . '"' : '' ?>
                 <?= !empty($f['required']) ? 'required' : '' ?>
                 <?= $hasError ? 'aria-invalid="true"' : '' ?>
                 <?= $describe ? 'aria-describedby="' . slap_e($describe) . '"' : '' ?>>
        <?php endif; ?>

        <?php if ($hasError): ?>
          <p class="field__error" id="<?= slap_e($errorId) ?>"><?= slap_e($errors[$key]) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endforeach; ?>

  <?php /* The trap: hidden from people, irresistible to scripts. Named
           meaninglessly so browser autofill has nothing to match — a honeypot
           called "company_website" gets filled in by Chrome and then real
           enquiries vanish behind a success message. */ ?>
  <div class="form__trap" aria-hidden="true">
    <label for="<?= slap_e(SLAP_TRAP_FIELD) ?>">Leave this empty</label>
    <input id="<?= slap_e(SLAP_TRAP_FIELD) ?>" name="<?= slap_e(SLAP_TRAP_FIELD) ?>" type="text" tabindex="-1" autocomplete="off">
  </div>
  <input type="hidden" name="<?= slap_e(SLAP_TIME_FIELD) ?>" value="<?= time() ?>">

  <button class="btn btn--coral form__send" type="submit">Send enquiry</button>

  <?php if (SLAP_ORG['whatsapp'] !== ''): ?>
    <p class="form__aside">
      Rather send photos on <a href="<?= slap_e(SLAP_ORG['whatsapp']) ?>" rel="noopener">WhatsApp</a>?
      That works too.
    </p>
  <?php endif; ?>
</form>
