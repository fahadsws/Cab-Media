<?php

namespace App\Support;

/**
 * Every service Cab Media offers: the agency core (creative, advertising, branding, media, associates, PR) plus the
 * digital, development, IT and AI lines. Each entry feeds a detail page at /services/{slug}. Copy is original.
 */
class ServicesCatalog
{
    /** Tabs on the home page / sections of the catalogue. */
    public const GROUPS = [
        'media' => ['Advertising & Media', 'The classic agency craft: ideas, campaigns, brands and media.'],
        'digital' => ['Digital Marketing', 'Be found, be remembered and keep customers coming back.'],
        'development' => ['Development', 'Websites, apps and software built around how you work.'],
        'it' => ['IT Services', 'The infrastructure and data foundations behind a reliable business.'],
        'ai' => ['AI Services', 'Practical AI inside your marketing, websites and decisions.'],
        'whatsapp' => ['WhatsApp Business API', 'Official WhatsApp messaging, automation and chatbots.'],
    ];

    /** Home page cards, in order: slug => [Unsplash photo id, short bullet list]. */
    public const CARDS = [
        'creative' => ['1572044162444-ad60f128bdea', ['Campaign (Pre & Post Production)', 'Design', 'Packaging', 'Innovation', 'Corporate Communication']],
        'advertising' => ['1699480114704-ac153307d2a0', ['Print', 'Digital & OTT', 'Television', 'Radio', 'OOH']],
        'branding' => ['1558655146-d09347e92766', ['Strategy', 'Positioning', 'Development', 'Research']],
        'media-planning-buying' => ['1557804506-669a67965ba0', ['National & International Media', 'Planning & Buying', 'Strategy', 'Implementation']],
        'business-associates' => ['1521791136064-7986c2920216', ['Pan India Marketing Associates for Print & TV', 'Notice Advertising across India']],
        'pr' => ['1540575467063-178a50c2df87', ['Strategy', 'Blog Writing', 'Influencer Marketing', 'Promoting Categories']],
        'digital-marketing' => ['1683721003111-070bcc053d8b', ['SEO/SEM', 'Paid ADS (Meta ADS, Google ADS)', 'Content Writing', 'Social Media Marketing', 'Website Related Services']],
        'development' => ['1498050108023-c5249f4df085', ['Website Development', 'Mobile App Development', 'Software Development']],
        'it-services' => ['1558494949-ef010cbdcc31', ['Domain Registration', 'Web Hosting', 'SSL Certificates', 'IT and Server Support', 'Data Analytics and Insights']],
        'ai-services' => ['1677442136019-21780ecad995', ['AI Optimized Web Development', 'AI Optimized SEO', 'AI Based Paid Media', 'AI Co-Pilot Strategy', 'Custom AI Development', 'AI Agent Development', 'AI Chatbot Development', 'AI CRM Development', 'LMS Development', 'AI Software Development', 'AI Automation & Integration']],
        'whatsapp-business-api' => ['1636751364472-12bfad09b451', ['WhatsApp Business API Onboarding (WABA Onboarding)', 'WhatsApp Business API Integration', 'WhatsApp Message Marketing', 'Bulk WhatsApp Marketing', 'WhatsApp Chatbot Automation (Click-Based & AI Agent Chatbots)', 'WhatsApp Chatbot Integration', 'WhatsApp Notifications & Transactional Messaging Solution']],
    ];

    /** Pages removed in the 2026 restructure => their replacement (301). */
    public const MOVED = [
        'seo' => 'digital-marketing', 'paid-advertising' => 'digital-marketing', 'owned-media-optimization' => 'digital-marketing', 'retention-marketing' => 'digital-marketing',
        'whatsapp-marketing' => 'whatsapp-business-api', 'enterprise-whatsapp' => 'whatsapp-business-api',
        'web-development' => 'development', 'mobile-app-development' => 'development', 'software-development' => 'development',
        'domain-hosting-ssl' => 'it-services', 'it-server-support' => 'it-services', 'data-analytics' => 'it-services',
        'ai-web-development' => 'ai-services', 'ai-seo' => 'ai-services', 'ai-paid-media' => 'ai-services', 'ai-copilot-strategy' => 'ai-services',
    ];

    public static function all(): array
    {
        static $all = null;
        return $all ??= self::build();
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function inGroup(string $group, bool $leafOnly = false): array
    {
        return array_filter(self::all(), fn ($s) => $s['group'] === $group && (! $leafOnly || ! ($s['category'] ?? false)));
    }

    /** Children of a category page. */
    public static function children(string $slug): array
    {
        $s = self::find($slug);
        return array_values(array_filter(array_map(fn ($c) => self::find($c), $s['children'] ?? [])));
    }

    /** Previous / next leaf service for the pager at the bottom of a detail page. */
    public static function neighbours(string $slug): array
    {
        $keys = array_keys(self::all());
        $i = array_search($slug, $keys, true);
        return [self::all()[$keys[($i - 1 + count($keys)) % count($keys)]], self::all()[$keys[($i + 1) % count($keys)]]];
    }

    private static function s(string $slug, string $group, string $title, string $icon, string $short, string $tagline, array $intro, array $offers, array $steps, array $benefits, array $faq, array $extra = []): array
    {
        return ['slug' => $slug, 'group' => $group, 'title' => $title, 'icon' => $icon, 'short' => $short, 'tagline' => $tagline, 'intro' => $intro, 'offers' => $offers, 'steps' => $steps, 'benefits' => $benefits, 'faq' => $faq] + $extra;
    }

    private static function build(): array
    {
        $list = [

            /* ------------------------------------------------------------ Advertising & Media */
            self::s('creative', 'media', 'Creative', 'pencil', 'Campaign ideas, design, packaging and corporate communication that people remember.',
                'Ideas and craft that make your brand impossible to scroll past.',
                ['Creative is where a brief turns into something people actually feel. Our team builds campaign ideas, designs and communication pieces that carry one clear message across every place your audience meets you.', 'We handle both ends of production, from the first concept through to the final delivery, so what is approved on paper is exactly what reaches the public.'],
                [['Campaign development', 'Concepts, scripts and storyboards, with pre-production and post-production managed in one flow.'], ['Design', 'Identity-led visuals for print, outdoor, digital and events that stay consistent from format to format.'], ['Packaging', 'Pack designs that stand out on the shelf and communicate the product in seconds.'], ['Innovation', 'Fresh formats and ideas for brands that want to be talked about, not just noticed.'], ['Corporate communication', 'Brochures, presentations, annual reports and internal communication with a clear voice.']],
                [['Brief & insight', 'We study the brief, the product and the audience to find the one thing worth saying.'], ['Concept', 'Two or three routes are developed and presented with reasoning, not just pictures.'], ['Production', 'The chosen route is produced, from shoot or design through edit and finishing.'], ['Delivery', 'Files, formats and versions are prepared for every channel you plan to use.']],
                ['One idea carried consistently across channels', 'Production quality that matches the quality of the thinking', 'Fewer hand-offs between concept and execution', 'Material ready for national and international use'],
                [['Do you handle both concept and production?', 'Yes. Cab Media takes a campaign from idea to finished assets, including pre- and post-production, so there is a single team accountable for the result.'], ['Can you work with our existing brand guidelines?', 'Absolutely. We start from your guidelines and extend them. If there are none, our branding team can create them.'], ['How many creative routes will we see?', 'Typically two to three routes for a campaign, each with a short rationale, so decisions are easy to make.']]),

            self::s('advertising', 'media', 'Advertising (National & International)', 'megaphone', 'Print, digital and OTT, television, radio and outdoor, planned and placed for reach.',
                'Be seen by the right people, in the right place, at the right moment.',
                ['Advertising is about putting your message in front of the audience that matters, without wasting spend on the one that does not. We run campaigns across every major medium, for brands in India and in international markets.', 'Because we think in terms of the whole mix, each medium is used for what it does best instead of repeating the same ad everywhere.'],
                [['Print', 'Newspaper and magazine advertising, from launch spreads to notices, in national and regional titles.'], ['Digital & OTT', 'Display, video and connected-TV placements that reach people on the screens they use most.'], ['Television', 'Spot and sponsorship planning on the channels and slots your audience actually watches.'], ['Radio', 'Audio campaigns with local relevance, strong for reach and frequency on a modest budget.'], ['OOH', 'Hoardings, transit and street furniture chosen by footfall and context, not just by size.']],
                [['Audience & goal', 'We agree who you want to reach and what you want them to do.'], ['Channel mix', 'A balanced plan across media is built against your budget.'], ['Booking & launch', 'Space and slots are negotiated, booked and scheduled.'], ['Tracking', 'Delivery is checked against the plan and the mix is adjusted while the campaign runs.']],
                ['Coverage across traditional and digital media in one plan', 'Buying power that helps stretch each rupee', 'A single point of contact for every placement', 'Transparent reporting on where the money went'],
                [['Do you advertise outside India?', 'Yes. We plan and place campaigns for national and international markets, depending on where your audience is.'], ['Can I run just one medium, for example outdoor?', 'Of course. Many clients start with a single medium and add others as results come in.'], ['Who designs the ad?', 'Our creative team can, or we can work from material you already have.']]),

            self::s('branding', 'media', 'Branding', 'tag', 'Strategy, positioning, identity development and research to give a brand a clear place in the market.',
                'A brand people can describe in one sentence, and recognise from across the street.',
                ['A brand is the sum of what you promise and what people experience. Our branding work starts with research and positioning, then turns the conclusions into an identity and a way of speaking that the whole company can use.', 'The result is a brand that is easier to market, easier to buy and easier to remember.'],
                [['Brand strategy', 'Purpose, audience and a clear promise that guide every later decision.'], ['Positioning', 'Where your brand sits against competitors, and why that place is worth owning.'], ['Brand development', 'Name, logo, visual identity, tone of voice and the guidelines that keep them consistent.'], ['Research', 'Customer and category research that replaces guesswork with evidence.']],
                [['Discover', 'Interviews, market and competitor review, and audience research.'], ['Define', 'Strategy and positioning are agreed in a short, shareable document.'], ['Design', 'Identity and voice are developed, tested and refined.'], ['Roll out', 'Guidelines and templates are handed over so the brand is applied consistently.']],
                ['A clearer reason for customers to choose you', 'Consistent look and voice across every touchpoint', 'Internal teams aligned around a single idea', 'A stronger base for pricing and growth'],
                [['How long does a branding project take?', 'A full strategy-to-identity project usually takes several weeks, depending on research needs and the number of stakeholders.'], ['Do we have to rebrand completely?', 'Not necessarily. Sometimes a refresh of positioning and visuals is enough. We recommend the smallest change that solves the problem.'], ['Will we receive brand guidelines?', 'Yes. Every identity comes with guidelines and the files your team and vendors need.']]),

            self::s('media-planning-buying', 'media', 'Media Planning & Buying', 'target', 'National and international media strategy, planning, buying and implementation.',
                'Every rupee of media spend, working harder.',
                ['Media planning decides where your money goes; media buying decides how well it is spent. We do both, building plans around audience data and negotiating the placements that deliver them.', 'Our team has worked with leading advertising, media and digital agencies, which helps in negotiations and in knowing which options genuinely perform.'],
                [['National & international media', 'Plans that cover home markets and overseas audiences in the same strategy.'], ['Planning & buying', 'Channel selection, scheduling and negotiated buying across print, TV, radio, outdoor and digital.'], ['Strategy', 'Reach, frequency and budget split worked out before a single booking is made.'], ['Implementation', 'Booking, material tracking, verification and post-campaign reconciliation.']],
                [['Objectives & budget', 'We set the target audience, the goal and the spending limit.'], ['Plan', 'Media options are evaluated and a recommended plan is presented.'], ['Buy', 'Placements are negotiated and booked at the best available terms.'], ['Report', 'Proof of delivery and results are shared, with learnings for the next flight.']],
                ['Better value from every placement', 'One plan across traditional and digital media', 'Clear reporting and verification', 'Advice that is independent of any single medium'],
                [['Do you charge for planning separately?', 'Scope and commercials are agreed upfront for each project, so there are no surprises. Please contact us for a quote.'], ['Can you buy media internationally?', 'Yes, for campaigns aimed at overseas audiences, subject to the markets involved.'], ['Do you verify that ads actually ran?', 'Yes. Proof of publication or airing is collected and shared as part of reporting.']]),

            self::s('business-associates', 'media', 'Business Associates', 'handshake', 'Pan-India marketing associates for print and TV, with competitive rates on notice advertising.',
                'A national network that keeps your announcements on time and on budget.',
                ['Some advertising, such as public notices, tenders and recruitment, has to appear in many places at once and by a fixed date. Our business associate network lets us book these across India through a single request.', 'You get a reliable process, competitive rates and one invoice instead of dozens.'],
                [['Pan-India print & TV associates', 'A network of partners that covers regional and national titles and channels.'], ['Notice advertising', 'Public notices, tenders, results and statutory advertisements, placed quickly and accurately.'], ['Competitive rates', 'Volume access and negotiation that help reduce the cost per insertion.'], ['Single-window handling', 'One brief, one coordinator and consolidated billing and proof.']],
                [['Send the matter', 'You share the text or artwork, the editions and the dates.'], ['Quote', 'We return a clear quote with options.'], ['Release', 'Orders are placed and the material is delivered to each publication.'], ['Proof', 'Clippings and certificates are collected and sent to you.']],
                ['Save time on multi-city bookings', 'Lower rates through consolidated buying', 'Deadlines handled by people who do this daily', 'Complete proof of publication'],
                [['How fast can a notice be placed?', 'Often within the same day, depending on the publication deadlines and the editions chosen.'], ['Can you help with design of the notice?', 'Yes. We can format or design it to publication specifications.'], ['Do you cover regional languages?', 'We work with titles across regions; please share your requirement and we will confirm coverage.']]),

            self::s('pr', 'media', 'PR', 'news', 'Strategy, blog writing, influencer marketing and category promotion that earn attention.',
                'Be talked about for the right reasons.',
                ['Public relations builds trust that advertising alone cannot buy. We plan how your story should be told, write it well, and take it to the media, creators and communities that matter to your audience.', 'Our PR work works hand in hand with branding and digital marketing so coverage supports sales, not just visibility.'],
                [['PR strategy', 'Messages, spokespeople, calendar and target media for the year ahead.'], ['Blog writing', 'Thoughtful articles that demonstrate expertise and bring visitors to your site.'], ['Influencer marketing', 'Creators chosen for fit with your audience, with clear briefs and tracking.'], ['Category promotion', 'Campaigns that raise awareness for a whole product category, not just one brand.']],
                [['Listen', 'We learn your story, your audience and how you are seen today.'], ['Plan', 'A communication plan with themes, formats and timing is agreed.'], ['Execute', 'Content is written, pitched and published with partners and creators.'], ['Review', 'Coverage and traffic are measured, and the plan is updated.']],
                ['Third-party credibility that builds trust', 'More relevant traffic and enquiries', 'A consistent story across channels', 'Content that continues to work long after publication'],
                [['Do you guarantee coverage?', 'No honest agency can. We pitch widely with strong stories and report openly on what is secured.'], ['Can you write blog content for us?', 'Yes. Our writers produce blog posts and articles in your tone, researched and edited.'], ['How do you pick influencers?', 'By audience fit, engagement quality and brand safety, not just follower count.']]),

            /* ------------------------------------------------------------ Digital Marketing */
            self::s('digital-marketing', 'digital', 'Digital Marketing', 'globe', 'Search, paid media, owned channels, retention and WhatsApp, planned as one growth engine.',
                'Digital marketing that connects every click to a customer.',
                ['Digital marketing works best when search, advertising, content and messaging are planned together. We start with your goals and then pick the mix of channels that can reach them in the most efficient way.', 'Below are the services we use, alone or combined.'],
                [['SEO/SEM', 'Organic search work plus paid search, so you are found when people look.'], ['Paid ADS (Meta Ads, Google Ads)', 'Targeted campaigns on Meta and Google, built around clear goals and tracked to results.'], ['Content Writing', 'Website copy, blogs and social content written to inform and convert.'], ['Social Media Marketing', 'Planning, posting and community management on the platforms your audience uses.'], ['Website Related Services', 'Landing pages, updates, speed and tracking fixes that keep your site working for you.']],
                [['Discover', 'We learn your goals, audience and what is already working.'], ['Plan', 'A clear scope, timeline and approach is agreed before work starts.'], ['Deliver', 'The work is built, tested and launched with regular check-ins.'], ['Improve', 'We review results and keep refining after launch.']], ['Search visibility and paid reach working together', 'Messages timed to where customers are in their journey', 'One reporting view across all channels'], []),





            self::s('whatsapp-business-api', 'whatsapp', 'WhatsApp Business API Solutions', 'chat', 'Bulk messaging, the official Business API and AI chatbots on India’s favourite messaging app.',
                'Reach customers where they already spend their day.',
                ['WhatsApp messages are opened far more often than most emails, which makes it a strong channel for offers, updates and support. We set up and run campaigns through the official WhatsApp Business API or through a lower-cost dashboard, depending on your needs.', 'Everything is built on consent, so your reputation and your number stay safe.'],
                [['WhatsApp Business API Onboarding (WABA Onboarding)', 'Get your business verified and approved on the official WhatsApp Business API.'], ['WhatsApp Business API Integration', 'Connect the API to your website, CRM and tools.'], ['WhatsApp Message Marketing', 'Campaigns that reach opted-in customers with offers and updates.'], ['Bulk WhatsApp Marketing', 'Send the same message to a small or large list in one go.'], ['WhatsApp Chatbot Automation (Click-Based & AI Agent Chatbots)', 'Automated replies, from simple click menus to AI agents that hold a conversation.'], ['WhatsApp Chatbot Integration', 'Plug chatbots into your existing systems and hand over to your team when needed.'], ['WhatsApp Notifications & Transactional Messaging Solution', 'Order, payment and appointment alerts delivered where customers will see them.']],
                [['Connect', 'Link an existing business number or set up a new one.'], ['Define', 'Choose the audience, message and media.'], ['Send', 'Launch the campaign to opted-in contacts.'], ['Review', 'Check delivery and responses, then refine.']],
                ['Very high attention compared with email', 'Real-time, two-way conversations', 'Low cost per conversation', 'Scales from a handful of contacts to large lists'],
                [['Is WhatsApp marketing allowed?', 'Yes, when you message people who have opted in and follow WhatsApp’s business policies. We build campaigns around this.'], ['How do I avoid being blocked?', 'Send relevant messages at sensible times, make opting out easy and avoid over-messaging.'], ['What is the Business API?', 'It is WhatsApp’s official interface for businesses, designed for larger-scale and automated communication.'], ['Can a chatbot handle my enquiries?', 'It can answer common questions and collect details, handing complex cases to your team.']]),


            /* ------------------------------------------------------------ Development */
            self::s('development', 'development', 'Development', 'code', 'Web, mobile and custom software built to fit your business.',
                'Technology built around your business, not the other way round.',
                ['Our development team builds the digital products your business runs on: websites, mobile apps and tailor-made software. We start with how you work and what you want to achieve, then choose the technology to suit.', 'The services included are listed below.'],
                [['Website Development', 'Business websites and online stores that are fast, secure and easy to manage.'], ['Mobile App Development', 'Android and iOS apps designed around how your customers actually use their phones.'], ['Software Development', 'Custom software that fits the way your business works.']],
                [['Discover', 'We learn your goals, audience and what is already working.'], ['Plan', 'A clear scope, timeline and approach is agreed before work starts.'], ['Deliver', 'The work is built, tested and launched with regular check-ins.'], ['Improve', 'We review results and keep refining after launch.']], ['Products designed around real workflows', 'One team for design, build and support', 'Scalable foundations for future growth'], []),




            /* ------------------------------------------------------------ IT Services */
            self::s('it-services', 'it', 'IT Services', 'server', 'Domains, hosting, SSL, server support and data analytics.',
                'The dependable technology foundation your business needs.',
                ['Behind every website, app and campaign sits infrastructure that has to stay secure and available. Our IT services cover the essentials: your online address, the servers that serve it, the support that keeps it running and the analytics that turn data into decisions.', 'The services included are listed below.'],
                [['Domain Registration', 'Find, register and renew the right domain name for your brand.'], ['Web Hosting', 'Reliable, secure hosting with backups and support.'], ['SSL Certificates', 'Encryption for your website so visitors and search engines trust it.'], ['IT and Server Support', 'Day-to-day support, monitoring and fixes for your servers and systems.'], ['Data Analytics and Insights', 'Turn sales, website and social data into clear reports you can act on.']],
                [['Discover', 'We learn your goals, audience and what is already working.'], ['Plan', 'A clear scope, timeline and approach is agreed before work starts.'], ['Deliver', 'The work is built, tested and launched with regular check-ins.'], ['Improve', 'We review results and keep refining after launch.']], ['One partner for infrastructure and marketing', 'Proactive support, not just emergency fixes', 'Decisions backed by your own data'], []),




            /* ------------------------------------------------------------ AI Services */
            self::s('ai-services', 'ai', 'AI Services', 'brain', 'AI-assisted websites, search, advertising and strategy, always with people in charge.',
                'Practical AI that improves results, guided by people who know your market.',
                ['AI is most useful when it is applied to a clear business problem. We use it to build smarter websites, find search opportunities earlier, run advertising more efficiently and support strategic decisions, with human experts reviewing every important step.', 'The services included are listed below.'],
                [['AI Optimized Web Development', 'Websites with personalisation and smart assistants built in.'], ['AI Optimized SEO', 'Predictive keyword research and AI-assisted content for search and answer engines.'], ['AI Based Paid Media', 'Predictive audiences, automated bidding and creative testing.'], ['AI Co-Pilot Strategy', 'AI-assisted analysis and forecasting that helps you decide faster.'], ['Custom AI Development', 'AI solutions designed around a specific problem in your business.'], ['AI Agent Development', 'Agents that complete tasks and handle workflows on your behalf.'], ['AI Chatbot Development', 'Chatbots that answer questions, capture leads and hand over to a person.'], ['AI CRM Development', 'CRM tools that score leads and automate follow-ups.'], ['LMS Development', 'Learning platforms for training your team or your customers.'], ['AI Software Development', 'Software with AI features built into the product from the start.'], ['AI Automation & Integration', 'Connect your tools and automate repeat work end to end.']],
                [['Discover', 'We learn your goals, audience and what is already working.'], ['Plan', 'A clear scope, timeline and approach is agreed before work starts.'], ['Deliver', 'The work is built, tested and launched with regular check-ins.'], ['Improve', 'We review results and keep refining after launch.']], ['Faster work and sharper insight', 'Human review at every important step', 'Clear measurement of what AI adds'], []),




        ];

        $out = [];
        foreach ($list as $s) $out[$s['slug']] = $s;
        return $out;
    }
}
