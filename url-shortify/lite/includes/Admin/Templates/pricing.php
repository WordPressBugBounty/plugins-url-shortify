<?php

use KaizenCoders\URL_Shortify\Helper;

$pricing_url = US()->get_pricing_url();

$pricing = [
    'yearly' => [
        '1_site'   => '$99',
        '3-sites'  => '$149',
        '25-sites' => '$299',
    ],

    'lifetime' => [
        '1_site'   => '$299',
        '3-sites'  => '$449',
        '25-sites' => '$599',
    ],
];

$plans = [
	[
		'is_free' => true,
		'title'   => 'Free',
		'desc'    => '',
		'price'   => [
			'yearly'   => '',
			'lifetime' => '',
		],
	],

	[
		'title' => 'For Bloggers',
		'desc'  => 'It\'s good for individual bloggers who have 1 blog.',
		'price' => [
			'yearly'   => $pricing['yearly']['1_site'],
			'lifetime' => $pricing['lifetime']['1_site'],
		],
	],

	[
		'title' => 'For Small Businesses',
		'desc'  => 'Businesses who have upto 3 websites.',
		'price' => [
			'yearly'   => $pricing['yearly']['3-sites'],
			'lifetime' => $pricing['lifetime']['3-sites'],
		],
	],

	[
		'title' => 'For Agencies',
		'desc'  => 'Businesses who have upto 25 websites.',
		'price' => [
			'yearly'   => $pricing['yearly']['25-sites'],
			'lifetime' => $pricing['lifetime']['25-sites'],
		],
	],
];

$feature_lists = [
	[
		'title' => 'Create Short Links',
	],
	[
		'title' => 'Temporary (302) Redirects',
	],
	[
		'title' => 'Temporary (307) Redirects',
	],
	[
		'title' => 'Permanent (301) Redirects',
	],
	[
		'title' => 'Custom Slugs',
	],
	[
		'title' => 'Forward URL Parameters',
	],
	[
		'title' => 'Nofollow Links',
	],
	[
		'title' => 'Simple Click Counting',
	],
	[
		'title' => 'Search Links',
	],
	[
		'title' => 'Group Short Links',
	],
	[
		'title' => 'Automatically Create Links for Pages, Posts',
	],
	[
		'title'      => 'Automatically Create Links for Custom Post Types.',
		'is_premium' => true,
	],
	[
		'title'      => 'Detailed Click Reporting',
		'is_premium' => true,
	],
	[
		'title'      => 'Generate Short links in bulk for Posts, Pages & Custom Post Types',
		'is_premium' => true,
	],
    [
		'title'      => 'Filter links by Group, Redirection Types, Link Parameters, based on Password Protections & time bound',
		'is_premium' => true,
	],
	[
		'title'      => 'Generate QR Codes',
		'is_premium' => true,
	],
	[
		'title'      => 'Password Protected Short Links',
		'is_premium' => true,
	],
	[
		'title'      => 'Traffic Routing',
		'is_premium' => true,
	],
	[
		'title'      => 'Link Rotation',
		'is_premium' => true,
	],
    [
		'title'      => 'Split Test (A/B Test)',
		'is_premium' => true,
	],
	[
		'title'      => 'Expiring Links',
		'is_premium' => true,
	],
	[
		'title'      => 'Retargeting',
		'is_premium' => true,
	],
	[
		'title'      => 'Access Control',
		'is_premium' => true,
	],
	[
		'title'      => 'UTM Presets',
		'is_premium' => true,
	],
	[
		'title'      => 'Cloaked Redirects',
		'is_premium' => true,
	],
	[
		'title'      => 'Meta-Refresh Redirects',
		'is_premium' => true,
	],
	[
		'title'      => 'Automatic Generate Short links for Custom Post Types',
		'is_premium' => true,
	],
	[
		'title'      => 'Custom Short link Slug Length',
		'is_premium' => true,
	],
	[
		'title'      => 'Filter Robots Clicks',
		'is_premium' => true,
	],
	[
		'title'      => 'IP Restriction',
		'is_premium' => true,
	],

	[
		'title'      => 'Case sensitive slugs',
		'is_premium' => true,
	],

	[
		'title'      => 'Exclude characters from slugs',
		'is_premium' => true,
	],

	[
		'title'      => 'Export links & clicks history',
		'is_premium' => true,
	],
	[
		'title'      => 'Custom Domains',
		'is_premium' => true,
	],
	[
		'title'      => 'Integration With WP All Imports',
		'is_premium' => true,
	],
	[
		'title'      => 'Anonymise Personal Data',
		'is_premium' => true,
	],

    [
		'title'      => '<b>Priority Support</b>',
		'is_premium' => true,
	],
];
?>


<!-- This example requires Tailwind CSS v2.0+ -->
<div class="bg-white" x-data="{toggle: 'yearly'}">
    <div class="">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-base font-semibold leading-7 text-indigo-600">Pricing</h2>
                <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Pricing plans for businesses
                    of all sizes</p>
            </div>
            <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">Start with Simple, Powerful
                and Easy URL Shortener. Convert your long, ugly links into clean, memorable, shareable links.</p>
            <div class="mt-16 mb-16 flex justify-center">
                <fieldset
                        class="grid grid-cols-2 gap-x-1 rounded-full p-1 text-center text-xs font-semibold leading-5 ring-1 ring-inset ring-gray-200">
                    <legend class="sr-only">Payment frequency</legend>

                    <!-- Checked: "bg-indigo-600 text-white", Not Checked: "text-gray-500" -->
                    <label for="toggle-yearly"
                           class="cursor-pointer rounded-full px-2.5 py-1"
                           :class="[toggle === 'yearly' ? 'bg-indigo-600 text-white' : 'text-gray-500']"
                    >
                        <input id="toggle-yearly"
                               type="radio"
                               name="frequency"
                               value="yearly"
                               class="sr-only"
                               x-model="toggle"
                        >
                        <span>Yearly</span>

                    </label>

                    <!-- Checked: "bg-indigo-600 text-white", Not Checked: "text-gray-500" -->
                    <label for="toggle-lifetime"
                           class="cursor-pointer rounded-full px-2.5 py-1"
                           :class="[toggle === 'lifetime' ? 'bg-indigo-600 text-white' : 'text-gray-500']"
                    >
                        <input id="toggle-lifetime"
                               type="radio"
                               name="frequency"
                               value="lifetime"
                               class="sr-only"
                               x-model="toggle"
                        >
                        <span>Lifetime</span>
                    </label>
                </fieldset>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto bg-white py-16 sm:py-24 sm:px-6 lg:px-8">
        <div class="hidden lg:block">
            <table class="w-full h-px table-fixed">
                <caption class="sr-only">
                    Pricing plan comparison
                </caption>
                <thead>
                <tr>
                    <th class="pb-4 px-6 text-sm font-medium text-gray-900 text-left" scope="col">
                        <span class="sr-only">Feature by</span>
                        <span>Plans</span>
                    </th>

					<?php foreach ( $plans as $plan ) { ?>
                        <th class="w-1/5 pb-4 px-6 text-lg leading-6 font-medium text-gray-900 text-left"
                            scope="col"><?php echo $plan['title']; ?></th>
					<?php } ?>
                </tr>
                </thead>
                <tbody class="border-t border-gray-200 divide-y divide-gray-200">
                <tr x-show="toggle === 'yearly'">
                    <th class="py-8 px-6 text-sm font-medium text-gray-900 text-left align-top" scope="row">Pricing</th>

					<?php foreach ( $plans as $plan ) { ?>
                        <td class="h-full py-8 px-6 align-top">
                            <div class="relative h-full table">
                                <p class="mb-16 text-sm text-red-600">
									<?php if ( '' !== \KaizenCoders\URL_Shortify\Helper::get_data( $plan, 'price|yearly', '' ) ) { ?>
                                        <span class="text-4xl font-extrabold text-red-600"><?php echo $plan['price']['yearly']; ?></span>
                                        <span class="text-base font-medium text-gray-500">/year</span>
									<?php } ?>
                                </p>
								<?php if ( ! isset( $plan['is_free'] ) ) { ?>
                                    <a href="<?php echo $pricing_url; ?>"
                                       class="absolute bottom-0 flex-grow block w-full bg-gray-800 border border-gray-800 rounded-md 5 py-2 text-sm font-semibold text-white text-center hover:bg-gray-900">Buy
                                        Now</a>
								<?php } ?>
                            </div>
                        </td>
					<?php } ?>
                </tr>

                <tr x-show="toggle === 'lifetime'">
                    <th class="py-8 px-6 text-sm font-medium text-gray-900 text-left align-top" scope="row">Pricing</th>

					<?php foreach ( $plans as $plan ) { ?>
                        <td class="h-full py-8 px-6 align-top">
                            <div class="relative h-full table">
                                <p class="mb-16 text-sm text-gray-500">
                                    <span class="text-4xl font-extrabold text-red-600"><?php echo $plan['price']['lifetime']; ?></span>
                                </p>
								<?php if ( ! isset( $plan['is_free'] ) ) { ?>
                                    <a href="<?php echo $pricing_url; ?>"
                                       class="absolute bottom-0 flex-grow block w-full bg-gray-800 border border-gray-800 rounded-md 5 py-2 text-sm font-semibold text-white text-center hover:bg-gray-900">Buy
                                        Now</a>
								<?php } ?>
                            </div>
                        </td>
					<?php } ?>
                </tr>

                <tr>
                    <th class="bg-gray-50 py-3 pl-6 text-sm font-medium text-gray-900 text-left" colspan="5"
                        scope="colgroup">Features
                    </th>
                </tr>

                <tr>
                    <th class="py-5 px-6 text-sm font-normal text-gray-500 text-left" scope="row">License Utilization
                    </th>

                    <td class="py-5 px-6">
                        <!-- Heroicon name: solid/minus -->
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span class="sr-only">Not included in Basic</span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>1 Site License</b></span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>3 Sites License</b></span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>25 Sites License</b></span>
                    </td>
                </tr>

				<?php foreach ( $feature_lists as $feature ) { ?>
                    <tr>
                        <th class="py-5 px-6 text-sm font-normal text-gray-500 text-left" scope="row">
							<?php echo $feature['title']; ?>
                        </th>

						<?php
						$is_premium_only_feature = isset( $feature['is_premium'] ) ? $feature['is_premium'] : false;
						?>


						<?php foreach ( $plans as $plan ) { ?>
                            <td class="py-5 px-6">
								<?php $is_free_plan = isset( $plan['is_free'] ) ? $plan['is_free'] : false;
								if ( $is_free_plan && $is_premium_only_feature ) { ?>
                                    <!-- Heroicon name: solid/close -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500"
                                         viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    <span class="sr-only">Not included in Basic</span>
								<?php } else { ?>
                                    <!-- Heroicon name: solid/check -->
                                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 20 20"
                                         fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                    <span class="sr-only">Included in Basic</span>
								<?php } ?>
                            </td>
						<?php } ?>
                    </tr>
				<?php } ?>

                <tr>
                    <th class="py-5 px-6 text-sm font-normal text-gray-500 text-left" scope="row">License Utilization
                    </th>

                    <td class="py-5 px-6">
                        <!-- Heroicon name: solid/minus -->
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span class="sr-only">Not included in Basic</span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>1 Site License</b></span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>3 Sites License</b></span>
                    </td>

                    <td class="py-5 px-6">
                        <span class="block text-sm text-gray-700"><b>25 Sites License</b></span>
                    </td>
                </tr>

                </tbody>
                <tfoot>
                <tr x-show="toggle === 'yearly'">
                    <th class="py-8 px-6 text-sm font-medium text-gray-900 text-left align-top" scope="row">Pricing</th>

					<?php foreach ( $plans as $plan ) { ?>
                        <td class="h-full">
                            <div class="relative h-full table">
                                <p class="mt-4 mb-16 text-sm text-gray-500">
									<?php if ( '' !== \KaizenCoders\URL_Shortify\Helper::get_data( $plan, 'price|yearly', '' ) ) { ?>
                                        <span class="text-4xl font-extrabold text-red-600"><?php echo $plan['price']['yearly']; ?></span>
                                        <span class="text-base font-medium text-gray-500">/year</span>
									<?php } ?>
                                </p>
								<?php if ( ! isset( $plan['is_free'] ) ) { ?>
                                    <a href="<?php echo $pricing_url; ?>"
                                       class="absolute bottom-0 flex-grow block w-full bg-gray-800 border border-gray-800 rounded-md 5 py-2 text-sm font-semibold text-white text-center hover:bg-gray-900">Buy
                                        Now</a>
								<?php } ?>
                            </div>
                        </td>
					<?php } ?>
                </tr>

                <tr x-show="toggle === 'lifetime'">
                    <th class="py-8 px-6 text-sm font-medium text-gray-900 text-left align-top" scope="row">Pricing</th>

					<?php foreach ( $plans as $plan ) { ?>
                        <td class="h-full">
                            <div class="relative h-full table">
                                <p class="mt-4 mb-16 text-sm text-gray-500">
                                    <span class="text-4xl font-extrabold text-red-600"><?php echo $plan['price']['lifetime']; ?></span>
                                </p>
								<?php if ( ! isset( $plan['is_free'] ) ) { ?>
                                    <a href="<?php echo $pricing_url; ?>"
                                       class="absolute bottom-0 flex-grow block w-full bg-gray-800 border border-gray-800 rounded-md 5 py-2 text-sm font-semibold text-white text-center hover:bg-gray-900">Buy
                                        Now</a>
								<?php } ?>
                            </div>
                        </td>
					<?php } ?>
                </tr>

                </tfoot>
            </table>
        </div>
    </div>
</div>