<?php

namespace App\Support;

/**
 * Copy of the public site (cabmedia.in). Kept apart from the Blade markup so it can later move to the
 * database / admin without touching the design. Text is taken from the current cabmedia.in website.
 */
class SiteContent
{
    public const NAV = [['Home', '#home'], ['About Us', '#about'], ['Services', '#services'], ['Testimonials', '#testimonials'], ['Blog', '/blog'], ['Contact', '#contact']];

    public const EMAIL = 'info@cabmedia.in';
    public const PHONES = [['+91 9820446872', '+919820446872'], ['+91 8805013828', '+918805013828']];

    public const OFFICES = ['Ahmedabad', 'Bengaluru', 'Chennai', 'Delhi', 'Mangalore', 'Lucknow', 'USA'];

    /** Rotating line in the hero (the three slides of the current site). */
    public const SLIDES = ['Advertising solutions for all communication needs', 'Banners in terms of digital and OOH', 'Communication is the core for reaching to the audiences'];

    /** [title, icon, items] */
    public const SERVICES = [
        ['Creative', 'pencil', ['Campaign (Pre & Post Production)', 'Design', 'Packaging', 'Innovation', 'Corp Communication']],
        ['Advertising (Natl & Intl)', 'megaphone', ['Print', 'Digital & OTT', 'Television', 'Radio', 'OOH']],
        ['Branding', 'tag', ['Strategy', 'Positioning', 'Development', 'Research']],
        ['Media Planning & Media Buying', 'target', ['National & International Media', 'Planning & Buying', 'Strategy', 'Implementation']],
        ['Business Associates', 'handshake', ['Pan India Marketing Associates for Print & TV', 'Best rate for notice ad Pan India']],
        ['PR', 'news', ['Strategy', 'Blog Writing', 'Influencer Marketing', 'Promoting Categories']],
        ['Digital Marketing', 'globe', ['Content Writing', 'Social Media Marketing', 'SEO/SEM', 'Website Related services']],
    ];

    /** [quote, name, company] */
    public const TESTIMONIALS = [
        ['I was put in touch with Cab Media through a recommendation from a good friend, since then we have been working with the team for more than three years now and would highly recommend them. The team is responsive, adaptable, reliable and a pleasure to work with. Cab Media has taken our initial media brief and product range and developed a quality brand identity, and provided associated marketing materials, we couldn’t be more pleased.', 'Mangala Lokhande', 'Vaidya Sane Ayurved Pvt Ltd - Madhavbaug'],
        ['Always a pleasure to work with the team at Cab Media, such professional and happy people and you know you’ll receive a quick, innovative and no fuss service.', 'Milind Wangikar', 'Empara Systems LLP'],
        ['We have always found that Cab Media and the team, provide just the right balance of professionalism, creativity and customer service to suit our needs. Having undertaken a number of projects with them, they have listened carefully on the brief and come up with something that hits the spot across a range of disciplines.', 'Amith Galaa', 'Prissm Pharma'],
        ['Excellent customer service, and very effective advertising. They have helped my business grow over a period of time, I would recommend Cab Media enthusiastically.', 'Ankit Chedda', 'Fashion Forecast India'],
        ['We have had a wonderful experience working with the team at Cab Media. Keep up the good work.', 'Ms. Aparna', 'Alom Education Pvt.Ltd'],
        ['Cab Media is a strong, forward-thinking company that is consistently engaged in providing the best media & marketing solutions for our brand.', 'Sandeep Sarvankar', 'Rudra International LLP'],
        ['Cab Media does a great job!! Glad we are working with them.', 'Dravyam LakeResorts', ''],
    ];

    /* ---- values editable in Admin > Settings > General (defaults above) ---- */
    public static function email(): string
    {
        return (string) (\App\Models\Setting::get('site.email') ?: self::EMAIL);
    }

    /** @return array<int,array{0:string,1:string}> [display label, tel: value] */
    public static function phones(): array
    {
        $raw = (string) \App\Models\Setting::get('site.phone', '');
        if (trim($raw) === '') return self::PHONES;
        $out = [];
        foreach (preg_split('/\s*[\/,;|\n]\s*/', $raw) as $p) {
            $p = trim($p);
            if ($p !== '') $out[] = [$p, preg_replace('/[^\d+]/', '', $p)];
        }
        return $out ?: self::PHONES;
    }

    /** @return array<int,string> */
    public static function offices(): array
    {
        $raw = (string) \App\Models\Setting::get('site.offices', '');
        $list = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $raw))));
        return $list ?: self::OFFICES;
    }

    public static function tagline(): string
    {
        return (string) (\App\Models\Setting::get('site.tagline') ?: 'One stop solution for all your media & marketing needs.');
    }

    /** Header / footer menu from Admin > Header & Footer; falls back to the built-in links when the admin has none. */
    public static function menu(string $location)
    {
        $tree = \App\Models\MenuItem::tree($location);
        if ($tree->count()) return $tree;

        $fallback = collect(self::NAV)->map(fn ($n) => (object) ['title' => $n[0], 'url' => '/'.ltrim($n[1], '/'), 'open_new_tab' => false, 'children' => collect()]);
        return $fallback;
    }
}
