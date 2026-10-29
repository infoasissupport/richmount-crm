<?php

/*
|--------------------------------------------------------------------------
| Quotation line item catalog
|--------------------------------------------------------------------------
| Standard work items offered by the builder's "Add from catalog" menu, per lead department
| (keys match leads.department). Each item is [description, unit]. The lead's own department is
| listed in full, other departments show their first two items under "Other trades".
*/

return [
    'construction' => [
        ['Site preparation & excavation', 'lump sum'],
        ['Reinforced concrete works', 'm³'],
        ['Blockwork & plastering', 'm²'],
        ['Structural steel supply & erection', 'ton'],
        ['Waterproofing membrane', 'm²'],
        ['MEP first fix', 'lump sum'],
    ],
    'interior' => [
        ['Gypsum ceiling works', 'm²'],
        ['Joinery & fitted furniture', 'lump sum'],
        ['Porcelain floor tiling', 'm²'],
        ['Painting & decoration', 'm²'],
        ['Glass partitions', 'm²'],
        ['Lighting fixtures', 'nos'],
    ],
    'renovation' => [
        ['Demolition & strip-out', 'lump sum'],
        ['Wall repairs & re-plastering', 'm²'],
        ['Façade cleaning & coating', 'm²'],
        ['Kitchen cabinets replacement', 'set'],
        ['Bathroom refurbishment', 'nos'],
    ],
    'mep' => [
        ['Split AC units supply & install', 'nos'],
        ['Ducting & insulation', 'm²'],
        ['DB & cabling works', 'lump sum'],
        ['Plumbing pipework', 'm'],
        ['Fire alarm devices', 'nos'],
        ['Testing & commissioning', 'lump sum'],
    ],
];
