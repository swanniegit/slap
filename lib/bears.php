<?php
/**
 * THE BEAR CATALOGUE — every bear SLAP has finished, in one array.
 *
 * The mockup gallery hard-coded four of these as "Caption to come" and repeated
 * the same twenty-line <figure> seven times with only the image swapped. This is
 * the data; partials/bear-card.php is the one piece of markup that renders it.
 *
 * 'label' is the care label printed on the card — the site's structural device.
 * It carries what a real sewn-in label carries: what the bear was made from and
 * what was deliberately kept. Rows are [caption, value] and render in order, so
 * a bear can say the true thing rather than fit a fixed schema.
 *
 * Append new bears at the end. Nothing addresses this list by index.
 */
declare(strict_types=1);

/** Filter collections, in the order the gallery chips appear. */
function slap_collections(): array
{
    return [
        'kit'       => 'Supporters’ kit',
        'nursery'   => 'Nursery fabric',
        'character' => 'Made new',
    ];
}

function slap_bears(): array
{
    return [
        [
            'slug'       => 'stormers',
            'image'      => '/assets/img/bears/stormers.jpeg',
            'name'       => 'The Stormers bear',
            'alt'        => 'A teddy bear sewn from a blue Stormers rugby jersey and cap, with the Vodacom Super Rugby badge on its chest',
            'collection' => 'kit',
            'route'      => 'memory',
            'blurb'      => 'Cut from a match jersey and the cap that went with it. The badge sits where a badge should sit, because the panels were placed before a stitch went in.',
            'label'      => [
                ['Made from', 'Stormers jersey and supporter cap'],
                ['Kept', 'Vodacom Super Rugby badge, Six Gun Grill sleeve print'],
            ],
        ],
        [
            'slug'       => 'blue-bulls',
            'image'      => '/assets/img/bears/blue-bulls.jpeg',
            'name'       => 'The Blue Bulls bear',
            'alt'        => 'A blue teddy bear sewn from Blue Bulls kit, wearing a mesh vest with a working zip, a cap and a team scarf',
            'collection' => 'kit',
            'route'      => 'memory',
            'blurb'      => 'A training top, a mesh vest and a cap. The zip still works, and the two Puma cats landed on the foot pads on purpose.',
            'label'      => [
                ['Made from', 'Blue Bulls training top, mesh vest and cap'],
                ['Kept', 'Working zip, Puma marks on both foot pads'],
            ],
        ],
        [
            'slug'       => 'sharks',
            'image'      => '/assets/img/bears/sharks.jpeg',
            'name'       => 'The Sharks bear',
            'alt'        => 'A black teddy bear sewn from a Sharks rugby jersey, wearing a hood with the Sharks emblem and a fringed team scarf',
            'collection' => 'kit',
            'route'      => 'memory',
            'blurb'      => 'Carbon-weave jersey, hood up, scarf on. The emblem came off the front of the shirt and went straight onto the head.',
            'label'      => [
                ['Made from', 'Sharks jersey and supporter scarf'],
                ['Kept', 'Sharks emblem, wordmark, scarf fringe'],
            ],
        ],
        [
            'slug'       => 'pooh-print',
            'image'      => '/assets/img/bears/pooh-print.jpeg',
            'name'       => 'The cot sheet bear',
            'alt'        => 'A pale blue teddy bear sewn from a nursery cot sheet printed with Winnie the Pooh characters, wearing a yellow sun hat and a ribbon bow',
            'collection' => 'nursery',
            'route'      => 'memory',
            'blurb'      => 'A first cot sheet, outgrown. Eeyore and Pooh were cut to land on the foot pads, so they show when the bear sits down.',
            'label'      => [
                ['Made from', 'A first cot sheet'],
                ['Kept', 'Eeyore and Pooh, one on each foot pad'],
            ],
        ],
        [
            'slug'       => 'nurse',
            'image'      => '/assets/img/bears/nurse.jpeg',
            'name'       => 'The nurse bear',
            'alt'        => 'A brown teddy bear in a white nurse’s pinafore with a red cross, and a white cap with red trim',
            'collection' => 'character',
            'route'      => 'character',
            'blurb'      => 'Made new for someone qualifying, retiring or being thanked. The pinafore comes off, and the pocket is a real pocket.',
            'label'      => [
                ['Made new in', 'Soft brown leatherette'],
                ['Finished with', 'Cotton pinafore, red cross, nurse’s cap'],
            ],
        ],
        [
            'slug'       => 'theatre-scrubs',
            'image'      => '/assets/img/bears/theatre-scrubs.jpeg',
            'name'       => 'The theatre bear',
            'alt'        => 'A brown teddy bear in grey theatre scrubs with a teal mask, scrub cap and booties, still pinned on the work table',
            'collection' => 'character',
            'route'      => 'character',
            'blurb'      => 'Scrubs, cap, mask and booties. Photographed still pinned, because that is what it looks like an hour before it is finished.',
            'label'      => [
                ['Made new in', 'Soft brown leatherette'],
                ['Finished with', 'Grey scrubs, teal cap, mask and booties'],
            ],
        ],
        [
            'slug'       => 'corduroy-pinafore',
            'image'      => '/assets/img/bears/corduroy-pinafore.jpeg',
            'name'       => 'The corduroy bear',
            'alt'        => 'A brown corduroy teddy bear in a yellow cotton pinafore with a hessian heart on the front, wearing a hessian sun hat',
            'collection' => 'character',
            'route'      => 'character',
            'blurb'      => 'Brown corduroy, a yellow pinafore and a hessian brim. The heart is hessian too, stitched on by hand rather than glued.',
            'label'      => [
                ['Made new in', 'Brown cotton corduroy'],
                ['Finished with', 'Yellow pinafore, hessian hat and heart'],
            ],
        ],
    ];
}

/** The catalogue, optionally narrowed to one collection slug. */
function slap_bears_in(?string $collection): array
{
    $all = slap_bears();
    if ($collection === null || !isset(slap_collections()[$collection])) {
        return $all;
    }
    return array_values(array_filter(
        $all,
        static fn(array $b): bool => $b['collection'] === $collection
    ));
}

/** The newest N bears, most recent first. Drives the home page strip. */
function slap_bears_recent(int $n): array
{
    return array_slice(array_reverse(slap_bears()), 0, $n);
}

/** One bear by slug. Throws rather than rendering an empty panel. */
function slap_bear(string $slug): array
{
    foreach (slap_bears() as $bear) {
        if ($bear['slug'] === $slug) {
            return $bear;
        }
    }
    throw new RuntimeException("No bear with slug '$slug' in lib/bears.php.");
}
