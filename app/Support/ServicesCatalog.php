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
                ['Digital marketing works best when search, advertising, content and messaging are planned together. We start with your goals and then pick the mix of channels that can reach them in the most efficient way.', 'Below are the services we use, alone or combined. Each has its own page with details.'],
                [], [], ['Search visibility and paid reach working together', 'Messages timed to where customers are in their journey', 'One reporting view across all channels'], [],
                ['category' => true, 'children' => ['seo', 'paid-advertising', 'owned-media-optimization', 'retention-marketing', 'whatsapp-marketing', 'enterprise-whatsapp']]),

            self::s('seo', 'digital', 'Search Engine Optimization', 'search', 'Local, national and e-commerce SEO that earns lasting organic traffic.',
                'Rank where your customers are already looking.',
                ['Search engine optimization improves how easily people find you on Google, Bing and other engines. We fix the technical basics, shape content around what customers really search for, and build authority over time.', 'Every programme is tailored to your industry, competition and goals, with reporting that connects rankings to leads and sales.'],
                [['National SEO', 'Broad visibility for brands that want to compete across the whole country.'], ['Local SEO', 'Visibility in specific cities or neighbourhoods for businesses that serve nearby customers.'], ['Small business SEO', 'Practical, affordable programmes for startups and local services.'], ['E-commerce SEO', 'Category and product page optimisation that brings buyers, not just visitors.'], ['Page speed & Core Web Vitals', 'Audits and fixes that make pages load quickly and feel stable on mobile.'], ['Industry-specific plans', 'Approaches adapted to sectors such as healthcare, finance, infrastructure and automotive.']],
                [['Set up', 'Goals, audience and competitors are mapped.'], ['Keyword research', 'High-value search terms are identified.'], ['On-page', 'Titles, content and structure are improved.'], ['Technical', 'Site architecture, speed and crawlability are fixed.'], ['Authority', 'Links and mentions are earned from relevant sources.'], ['Report', 'Rankings, traffic and leads are tracked and reviewed regularly.']],
                ['Steady, compounding organic traffic', 'More qualified enquiries', 'Better site experience for every visitor', 'Clear, honest reporting'],
                [['How long does SEO take to work?', 'Early improvements can show within a few months, but meaningful growth usually builds over six months or more, depending on competition.'], ['Will my website need changes?', 'Often yes: content, structure and technical fixes. We explain each recommendation and why it matters.'], ['Is SEO better than ads?', 'They do different jobs. Ads give quick reach while SEO builds lasting visibility. Many brands use both.'], ['Can I measure success?', 'Yes, through rankings, organic traffic, enquiries and conversions in a regular report.']]),

            self::s('paid-advertising', 'digital', 'Paid Advertising', 'bolt', 'Social, search, display, programmatic and affiliate campaigns tuned for return.',
                'Pay for attention that turns into action.',
                ['Paid media gets your offer in front of ready audiences quickly. We plan, create and optimise campaigns across platforms, and judge them by the results that matter to you: leads, sales or awareness.', 'Budgets are watched daily and moved to what works.'],
                [['Social media advertising', 'Campaigns on Facebook, Instagram, LinkedIn and YouTube built around each platform’s audience and format.'], ['Search & display', 'Search ads for people actively looking, and banners and rich media for reach and remarketing.'], ['Programmatic', 'Automated, data-led buying across many sites and apps with precise audience segments.'], ['Affiliate marketing', 'Partner programmes where affiliates promote your offer and are paid on performance.']],
                [['Plan', 'Goals, audiences and budget are set.'], ['Create', 'Ads and landing pages are designed and written.'], ['Launch', 'Campaigns go live with tracking in place.'], ['Optimise', 'Bids, audiences and creatives are tuned continuously.'], ['Report', 'Results are shared with clear next steps.']],
                ['Faster results than organic channels', 'Precise control of audience and budget', 'Measurable return on every campaign', 'Flexibility to scale up or pause'],
                [['What budget do I need?', 'It depends on the market and goal. We recommend a starting budget after reviewing your category.'], ['Which platform should I use?', 'The one where your audience pays attention. We test before committing large budgets.'], ['Do you create the ads too?', 'Yes. Creative, copy and landing-page advice are included in the service.']]),

            self::s('owned-media-optimization', 'digital', 'Owned Media Optimization', 'doc', 'Make your website, social profiles, press releases and creator partnerships work harder.',
                'Get more from the channels you already control.',
                ['Owned and earned media are the foundation of a brand’s online presence: your site, your social profiles, your press releases and your relationships with creators. Optimising them raises visibility without paying for every impression.', 'We bring search, social, press distribution and influencer work together in one coordinated plan.'],
                [['Search optimisation', 'Keyword research, competitor review and on-page and off-page improvements.'], ['Social media optimisation', 'Profile and content improvements, hashtags, posting rhythm and formats that invite interaction.'], ['Press release distribution', 'News written and sent to wires, trade publications and social channels.'], ['Influencer partnerships', 'Creators whose audiences match yours, for authentic recommendations.']],
                [['Audit', 'We review your current channels and audience.'], ['Plan', 'Priorities and a content calendar are set.'], ['Optimise', 'Profiles, pages and content are improved.'], ['Distribute', 'News and creator content go out across channels.'], ['Review', 'Reports guide further improvement.']],
                ['Higher organic reach', 'A stronger, more consistent brand presence', 'Wider media coverage', 'Trust that comes from third-party voices'],
                [['What counts as owned media?', 'Anything you control: website, blog, social accounts, newsletters and similar channels.'], ['Do you write press releases?', 'Yes, and we also distribute them to relevant outlets.'], ['Can you handle social posting too?', 'Yes. Content planning and publishing can be included.']]),

            self::s('retention-marketing', 'digital', 'Retention Marketing', 'mail', 'Email, SMS, RCS and IVR programmes that keep customers engaged and buying.',
                'Customers you have already won are your best growth channel.',
                ['Keeping a customer is almost always cheaper than finding a new one. Retention marketing uses permission-based messages, sent at the right moment, to keep people informed, loyal and ready to return.', 'We design and run programmes across email, SMS, RCS, WhatsApp and voice, and measure what each message achieves.'],
                [['Email marketing', 'Promotional, transactional and service emails with segmentation and automation.'], ['SMS marketing', 'Short, timely text campaigns with a clear call to action.'], ['RCS messaging', 'Rich messages with images, buttons and read receipts for supported devices.'], ['IVR services', 'Automated phone menus that greet callers, answer common questions and route them to the right person.'], ['WhatsApp campaigns', 'Opt-in broadcasts and updates, covered in more detail on their own page.']],
                [['Segment', 'We group customers by behaviour and value.'], ['Choose channels', 'Each message goes where it works best.'], ['Craft & schedule', 'Content and timing are planned and automated.'], ['Measure', 'Opens, clicks and orders are tracked to refine the programme.']],
                ['Higher repeat purchase and loyalty', 'Lower cost than constant new acquisition', 'Personal, relevant communication', 'Less load on your support team with automation'],
                [['Do you need customer consent?', 'Yes. We only run permission-based campaigns that follow the applicable rules and platform policies.'], ['Which channel is best?', 'It depends on your audience. Often a combination works, such as email for detail and SMS or WhatsApp for urgency.'], ['Can we start with one channel?', 'Yes. Starting with one and expanding is common.']]),

            self::s('whatsapp-marketing', 'digital', 'WhatsApp Marketing', 'chat', 'Bulk messaging, the official Business API and AI chatbots on India’s favourite messaging app.',
                'Reach customers where they already spend their day.',
                ['WhatsApp messages are opened far more often than most emails, which makes it a strong channel for offers, updates and support. We set up and run campaigns through the official WhatsApp Business API or through a lower-cost dashboard, depending on your needs.', 'Everything is built on consent, so your reputation and your number stay safe.'],
                [['Bulk messaging', 'Send the same message to a small or large list in one go.'], ['Official Business API', 'A verified connection to WhatsApp for secure, compliant messaging at scale.'], ['AI chatbots', 'Automated replies to common questions, with lead capture and hand-over to a person.'], ['Campaign dashboard', 'A simpler tool to manage contacts, messages and reports.'], ['Rich media', 'Images, videos, documents and links inside the conversation.']],
                [['Connect', 'Link an existing business number or set up a new one.'], ['Define', 'Choose the audience, message and media.'], ['Send', 'Launch the campaign to opted-in contacts.'], ['Review', 'Check delivery and responses, then refine.']],
                ['Very high attention compared with email', 'Real-time, two-way conversations', 'Low cost per conversation', 'Scales from a handful of contacts to large lists'],
                [['Is WhatsApp marketing allowed?', 'Yes, when you message people who have opted in and follow WhatsApp’s business policies. We build campaigns around this.'], ['How do I avoid being blocked?', 'Send relevant messages at sensible times, make opting out easy and avoid over-messaging.'], ['What is the Business API?', 'It is WhatsApp’s official interface for businesses, designed for larger-scale and automated communication.'], ['Can a chatbot handle my enquiries?', 'It can answer common questions and collect details, handing complex cases to your team.']]),

            self::s('enterprise-whatsapp', 'digital', 'Enterprise WhatsApp Solutions', 'lock', 'Secure, high-volume WhatsApp for large and regulated organisations, integrated with your systems.',
                'WhatsApp at enterprise scale, with the controls big organisations need.',
                ['Large organisations need more than a broadcast tool. They need security, data control, high-volume delivery and a link to the systems that hold customer information.', 'Our enterprise WhatsApp service deploys the Business API with the right hosting model, connects it to your CRM or ERP, and adds automation and human hand-off for support.'],
                [['Compliant deployment', 'Set-up aligned with the data protection and security requirements that apply to your sector.'], ['Hosting choices', 'Cloud or client-controlled hosting depending on your governance needs.'], ['Conversational automation', 'Structured bots with live-agent hand-over for support and service journeys.'], ['Transactional messaging', 'High-volume alerts, reminders, statements and notifications.'], ['Secure authentication', 'One-time passcodes and sign-in verification flows.'], ['System integration', 'Connections to CRM, ERP and other internal platforms.'], ['Ongoing support', 'Monitoring and technical support after go-live.']],
                [['Assess', 'We review communication needs, security and compliance.'], ['Design', 'The architecture, data flows and message templates are planned.'], ['Deploy', 'The API is set up on the chosen hosting model.'], ['Integrate', 'Your systems are connected and tested.'], ['Launch & scale', 'Flows go live, with support as volumes grow.']],
                ['Faster, more engaging communication than SMS or email', 'Lower contact-centre workload', 'Control over where data lives', 'A platform that scales with volume'],
                [['How is this different from the normal business app?', 'The enterprise API is built for high volume, automation and system integration, not manual chats on a phone.'], ['Can it connect to our CRM?', 'Yes. Integrations with CRM, ERP and other systems are part of the service.'], ['What about data protection?', 'We plan the deployment around the regulations and policies that apply to your organisation.']]),

            /* ------------------------------------------------------------ Development */
            self::s('development', 'development', 'Development', 'code', 'Web, mobile and custom software built to fit your business.',
                'Technology built around your business, not the other way round.',
                ['Our development team builds the digital products your business runs on: websites, mobile apps and tailor-made software. We start with how you work and what you want to achieve, then choose the technology to suit.', 'Explore the three areas below.'],
                [], [], ['Products designed around real workflows', 'One team for design, build and support', 'Scalable foundations for future growth'], [],
                ['category' => true, 'children' => ['web-development', 'mobile-app-development', 'software-development']]),

            self::s('web-development', 'development', 'Web Development', 'code', 'Business websites, e-commerce stores and custom web platforms, built to convert.',
                'A website that earns its place as your best salesperson.',
                ['Your website is often the first thing a customer sees, and sometimes the only thing. We design and build sites that are fast, easy to use on every device and simple for your team to update.', 'From a five-page brochure site to a full online store, we match the build to your goals and budget.'],
                [['WordPress development', 'Flexible, editable sites on the world’s most widely used content platform.'], ['E-commerce development', 'Online stores with catalogues, carts, payments and order management.'], ['Custom development', 'Bespoke sites and web applications when off-the-shelf options do not fit.'], ['Static websites', 'Fast, simple sites for fixed content with minimal upkeep.'], ['Responsive design', 'Layouts that adapt cleanly to phones, tablets and desktops.'], ['Redesign', 'A fresh look, better usability and better performance for an ageing site.'], ['Maintenance', 'Updates, fixes, backups and security monitoring after launch.']],
                [['Requirements', 'We learn your goals, audience and must-have features.'], ['Plan & wireframes', 'Structure and page layouts are mapped before design.'], ['Approval', 'You review and sign off on the design direction.'], ['Build', 'The site is developed with modern, maintainable tools.'], ['Test', 'Function, speed and mobile behaviour are checked thoroughly.'], ['Launch & support', 'The site goes live and is looked after.']],
                ['A professional online presence around the clock', 'Easier navigation and lower bounce rates', 'Content you can edit without a developer', 'Secure, well-maintained code'],
                [['How long does a website take?', 'A simple site can take a few weeks; larger or custom projects take longer. We confirm a timeline once the scope is clear.'], ['Can I edit the content myself?', 'Yes. Sites are built with a content system so non-technical staff can update pages.'], ['Do you offer maintenance?', 'Yes, through monthly maintenance plans covering updates, backups and security.'], ['What is the difference between design and development?', 'Design decides how the site looks and flows; development builds it so it works. We do both.']]),

            self::s('mobile-app-development', 'development', 'Mobile App Development', 'mobile', 'Android, iOS and cross-platform apps designed around your business goals.',
                'Put your business in your customers’ pockets.',
                ['A good app does one job brilliantly. We help you work out which job, design an experience that feels natural on the phone, and build it for the platforms your users prefer.', 'We can build separate native apps or a single cross-platform app that works on both, depending on budget and performance needs.'],
                [['Android apps', 'Native apps for the Android ecosystem, tuned for performance and a wide range of devices.'], ['iOS apps', 'Native apps for iPhone and iPad with the polish Apple users expect.'], ['Hybrid / cross-platform apps', 'One codebase for both platforms, using frameworks such as Flutter, for a more economical route.']],
                [['Idea & scope', 'We define the problem, users and core features.'], ['Design', 'Screens and flows are prototyped for early feedback.'], ['Build', 'The app is developed in short cycles you can review.'], ['Test', 'It is tested on real devices before release.'], ['Release & support', 'We publish to the stores and support the app afterwards.']],
                ['A direct, always-available channel to customers', 'Experience designed for touch and mobile habits', 'Choice between native and cross-platform to suit budget', 'Support from first release onward'],
                [['Native or cross-platform?', 'Native gives the best performance on one platform; cross-platform lets you reach both with one build. We recommend based on your needs.'], ['Can you publish the app for us?', 'Yes. We prepare and submit it to the app stores.'], ['Do you build apps connected to my website?', 'Yes. Apps can share data and accounts with your website or back-office systems.']]),

            self::s('software-development', 'development', 'Software Development', 'app', 'Custom e-commerce, ERP and CRM software shaped around your processes.',
                'Software that fits like it was made for you, because it was.',
                ['When standard tools force you to work around them, custom software is worth considering. We study your goals and processes, design the right architecture and build and test the system with you.', 'Our focus is on solutions that are reliable, scalable and straightforward for your team to use.'],
                [['E-commerce software', 'Online store platforms with inventory, orders, payments and customer tools.'], ['ERP software', 'Systems that connect finance, stock, sales and operations in one place.'], ['CRM software', 'Tools to manage leads, sales pipelines and customer conversations, integrated with your other systems.']],
                [['Analyse', 'Business analysts map goals, users and requirements.'], ['Design', 'Architecture and interfaces are planned.'], ['Develop', 'The software is built in stages, with regular demos.'], ['Test', 'Quality assurance and user testing before release.'], ['Deploy & support', 'Roll-out, training and continued care.']],
                ['Software aligned with how you really work', 'One source of truth for your data', 'Room to add features as you grow', 'Fewer manual workarounds'],
                [['Do I need custom software or an off-the-shelf product?', 'If a ready-made product fits most of your needs, we will say so. Custom makes sense when your process is a competitive advantage or off-the-shelf tools fall short.'], ['Can you integrate with existing systems?', 'Yes. Integration with accounting, payment, CRM and other tools is common.'], ['Who owns the software?', 'Ownership and licensing are agreed in the project contract.']]),

            /* ------------------------------------------------------------ IT Services */
            self::s('it-services', 'it', 'IT Services', 'server', 'Domains, hosting, SSL, server support and data analytics.',
                'The dependable technology foundation your business needs.',
                ['Behind every website, app and campaign sits infrastructure that has to stay secure and available. Our IT services cover the essentials: your online address, the servers that serve it, the support that keeps it running and the analytics that turn data into decisions.', 'Explore the three areas below.'],
                [], [], ['One partner for infrastructure and marketing', 'Proactive support, not just emergency fixes', 'Decisions backed by your own data'], [],
                ['category' => true, 'children' => ['domain-hosting-ssl', 'it-server-support', 'data-analytics']]),

            self::s('domain-hosting-ssl', 'it', 'Domain, Hosting & SSL', 'cloud', 'Registration, reliable hosting and SSL certificates, set up and managed for you.',
                'Your address, your home and your padlock, sorted.',
                ['Every online presence rests on three things: a domain name people can remember, hosting that keeps your site available, and an SSL certificate that proves it is safe. We register, configure and renew all three so you never have to chase an expiry date.', 'Everything can be bundled with a new website or added to an existing one.'],
                [['Domain registration', 'Choose and secure a name that represents your brand, with the right extension for your audience.'], ['Hosting', 'Servers sized for your traffic that keep the site online and fast.'], ['SSL certificates', 'Encryption between visitors and your site, plus the trust signal browsers show.'], ['Renewals & management', 'Reminders, renewals and DNS changes handled for you.']],
                [['Choose', 'We help you pick the name and plan.'], ['Register & set up', 'Domain, hosting and certificate are configured together.'], ['Connect', 'Email and website are pointed to the new set-up.'], ['Maintain', 'Renewals and security checks continue quietly in the background.']],
                ['A trusted, secure browsing experience', 'Reliable uptime', 'No missed renewals', 'A single place for all your web essentials'],
                [['How do I choose a domain name?', 'Aim for short, easy to spell and relevant to your brand. We can suggest options and check availability.'], ['Why do I need SSL?', 'It encrypts data and removes the “not secure” warning browsers display. It also helps search visibility.'], ['Can you move my existing site?', 'Yes. We can migrate your site and email with minimal disruption.']]),

            self::s('it-server-support', 'it', 'IT & Server Support', 'server', 'Consulting, migration, hosting management and cloud support for steady, secure operations.',
                'Servers that simply work, so your team does not have to think about them.',
                ['Slow pages, downtime and security worries cost money. Our IT and server support team plans, moves and looks after your infrastructure so it is fast, secure and available when customers need it.', 'We work with common hosting providers and website platforms and can manage the environment on an ongoing basis.'],
                [['IT consulting', 'Assessment of your needs and a plan for performance, security and cost.'], ['Server migration', 'Moving servers between providers with minimal downtime.'], ['Website hosting & migration', 'Hosting and moving sites built on WordPress, PHP applications and popular store platforms.'], ['Ongoing management', 'Monitoring, updates, backups and troubleshooting.'], ['Cloud solutions', 'Cloud-based infrastructure sized for your workload.']],
                [['Assess', 'We understand your applications and requirements.'], ['Plan', 'We design the target set-up and migration path.'], ['Implement', 'Changes are applied and tuned for performance.'], ['Manage', 'We monitor and maintain the environment.'], ['Scale', 'Cloud resources are adjusted as you grow.']],
                ['Reliable uptime and faster pages', 'Stronger security posture', 'Smooth migrations without lost business', 'Expert help on call'],
                [['Which hosting providers do you work with?', 'We work with the major cloud and hosting providers. Tell us what you use or want to move to.'], ['How do you avoid downtime during a migration?', 'By preparing and testing the new environment first, then switching over at a quiet time.'], ['Do you offer ongoing support?', 'Yes, on a monthly basis, covering monitoring, updates and support requests.']]),

            self::s('data-analytics', 'it', 'Data Analytics & Insights', 'chart', 'Turn sales, website and social data into clear, usable insight.',
                'Know what is working, and why, before you spend another rupee.',
                ['Most businesses already collect valuable data in their sales records, website and social channels. The difficulty is making sense of it. We bring those sources together, look for patterns and present findings in a form decision makers can use.', 'The aim is simple: fewer assumptions and better choices.'],
                [['Customer transaction analysis', 'What people buy, when and in what combinations.'], ['Website traffic analysis', 'Where visitors come from, what they do and where they leave.'], ['Social engagement analysis', 'Which content and channels spark real interaction.'], ['Trends & patterns', 'Recurring signals across your data sources.'], ['Improvement opportunities', 'Specific areas where a change is likely to pay off.']],
                [['Connect', 'We identify and link your data sources.'], ['Clean', 'Data is checked and prepared for analysis.'], ['Analyse', 'We explore patterns and test questions that matter to you.'], ['Present', 'Findings are delivered in clear dashboards and summaries.'], ['Act', 'We recommend next steps and measure the effect.']],
                ['Decisions based on evidence', 'A clear picture of customer behaviour', 'Early warning of problems', 'Marketing budgets directed to what works'],
                [['What data do you need?', 'Typically sales records, website analytics and social platform data. We tell you exactly what is useful.'], ['How are insights delivered?', 'As dashboards and short written summaries, with a review conversation.'], ['Is my data safe?', 'We handle your data under agreed confidentiality terms and only for the agreed purpose.']]),

            /* ------------------------------------------------------------ AI Services */
            self::s('ai-services', 'ai', 'AI Services', 'brain', 'AI-assisted websites, search, advertising and strategy, always with people in charge.',
                'Practical AI that improves results, guided by people who know your market.',
                ['AI is most useful when it is applied to a clear business problem. We use it to build smarter websites, find search opportunities earlier, run advertising more efficiently and support strategic decisions, with human experts reviewing every important step.', 'Explore the four areas below.'],
                [], [], ['Faster work and sharper insight', 'Human review at every important step', 'Clear measurement of what AI adds'], [],
                ['category' => true, 'children' => ['ai-web-development', 'ai-seo', 'ai-paid-media', 'ai-copilot-strategy']]),

            self::s('ai-web-development', 'ai', 'AI-Optimised Web Development', 'spark', 'Websites with personalisation, smart assistants and analytics built in.',
                'A website that adapts to each visitor instead of showing everyone the same page.',
                ['Traditional sites present the same content to every visitor. AI-optimised sites can tailor what people see, answer their questions through an assistant and learn from how they behave.', 'We combine solid web engineering with carefully chosen AI features, so the site feels helpful rather than gimmicky.'],
                [['Personalised journeys', 'Content and layouts that respond to visitor behaviour and interests.'], ['Smart chat assistants', 'Round-the-clock help that answers common questions and captures leads.'], ['Predictive analytics', 'Insight that shows which paths lead to enquiries and sales.'], ['Full-stack build', 'Front end and back end developed together with AI features included.'], ['Content upkeep', 'AI-assisted updates that keep pages current with less manual effort.'], ['Performance & security', 'Speed tuning, accessibility and privacy-conscious design.'], ['Cloud deployment', 'Scalable hosting with automated release pipelines.']],
                [['Discovery', 'We clarify goals, audience and priorities.'], ['Design & prototype', 'Journeys and assistant flows are mapped and tested.'], ['Build', 'The site is developed, with specialists on the custom parts.'], ['Test', 'We test behaviour, accessibility and speed.'], ['Launch & improve', 'We go live and refine from real usage data.']],
                ['More engaging visitor experiences', 'Faster answers and fewer missed enquiries', 'Scales smoothly with traffic', 'Data to improve continuously'],
                [['How is this different from a normal website?', 'It adds adaptive content, assistants and analytics on top of the usual build, to respond to each visitor.'], ['Is it suitable for small businesses?', 'Yes. We scope features to your size and budget and add more over time.'], ['How do you keep it secure?', 'We follow secure coding practice, limit the data collected and respect privacy rules.']]),

            self::s('ai-seo', 'ai', 'AI-Optimised SEO', 'search', 'Predictive keyword research, AI-assisted content and answer-engine optimisation.',
                'Be the answer, whether people search on Google or ask an AI.',
                ['People now get answers from search results and from AI assistants. AI-optimised SEO prepares your content for both: it finds rising topics early, produces helpful content faster, and structures it so it can be quoted in answers.', 'Writers and editors review everything, so the content keeps your voice and stays accurate.'],
                [['Technical SEO audit', 'A review of pages, search intent and competitors.'], ['Predictive keyword research', 'Rising topics and keywords identified before they become crowded.'], ['Content creation', 'Regular articles and guides drafted with AI and refined by human writers.'], ['Answer-engine optimisation', 'Structured data and FAQ markup so your content can appear in AI-generated answers.'], ['Technical improvements', 'Speed, Core Web Vitals, sitemaps and smarter internal linking.'], ['Reporting', 'Visibility, traffic and conversions in one view.'], ['Strategy reviews', 'Regular sessions to adjust the plan.']],
                [['Audit', 'We review current content and search intent.'], ['Plan', 'Topic clusters and a content calendar are agreed.'], ['Create', 'Content is drafted, edited and approved.'], ['Optimise', 'Structure, schema and technical fixes are applied.'], ['Track & scale', 'We monitor results monthly and expand what works.']],
                ['Visibility in both search and AI answers', 'Faster content production without lowering quality', 'Authority that builds over time', 'Measurable return'],
                [['What is answer-engine optimisation?', 'It is structuring your content so AI assistants and search features can find, understand and cite it.'], ['Is the content written by AI?', 'AI assists with drafting and research; experienced editors review and finalise every piece.'], ['How soon will I see results?', 'Content and technical fixes can show early movement in a few months; authority grows over time.']]),

            self::s('ai-paid-media', 'ai', 'AI-Based Paid Media', 'bolt', 'Predictive audiences, automated bidding and creative testing across major ad platforms.',
                'Advertising that learns from every impression.',
                ['AI can spot patterns in audience behaviour and ad performance that people miss. We use it to build better audiences, test creatives faster and adjust bids as conditions change, while strategists keep control of the goals.', 'The result is a campaign that keeps improving instead of just running.'],
                [['Audience modelling', 'High-value segments and lookalike audiences built from your data.'], ['Creative testing', 'Images, video and copy tested and scored to find winners quickly.'], ['Bidding & budget automation', 'Bids adjusted by likelihood of conversion, including return-on-ad-spend targets.'], ['Multi-platform management', 'Google, Meta and programmatic coordinated rather than run in silos.'], ['Analytics & forecasts', 'Dashboards and regular reviews of performance.'], ['Privacy & fraud checks', 'Tools that protect budgets and respect user privacy.']],
                [['Audit', 'We review data, accounts and past campaigns.'], ['Blueprint', 'Audiences and creative concepts are planned.'], ['Launch', 'Campaigns are built with testing from day one.'], ['Optimise', 'Bids and budgets move to what performs.'], ['Report & iterate', 'Results are reviewed and the next cycle planned.']],
                ['Better return on ad spend', 'Less manual optimisation', 'Faster learning from creative tests', 'Decisions based on data'],
                [['Do I need a large budget?', 'Automation works best with enough data, but we can adapt the approach for smaller budgets.'], ['Who controls the strategy?', 'Our strategists set the goals and guardrails; AI handles the repetitive optimisation.'], ['Which platforms do you use?', 'Mainly Google and Meta, plus programmatic networks where they suit the goal.']]),

            self::s('ai-copilot-strategy', 'ai', 'AI Co-Pilot Strategy', 'brain', 'AI-assisted analysis and forecasting that helps leaders decide faster and with more confidence.',
                'Strategy that pairs machine speed with human judgement.',
                ['Good strategy depends on seeing your market clearly. Our AI co-pilot approach combines analytics and predictive models with experienced consultants, helping you spot gaps, test scenarios and choose direction with more evidence.', 'It suits both start-ups finding their footing and established companies planning their next phase.'],
                [['Discovery workshop', 'A focused session to map stakeholders, goals and the quality of your data.'], ['Analytics dashboard', 'A clear, current view of the numbers that matter.'], ['Predictive models', 'Forecasts for demand, pricing or competitor moves.'], ['Competitive intelligence', 'A regular review of trends and opportunities in your market.'], ['Strategy playbook', 'Tactics, targets and templates for putting the plan to work.'], ['Responsible-AI review', 'A check for bias, privacy and compliance before models are used.'], ['Ongoing support', 'Regular reviews and model updates.']],
                [['Align', 'We learn your goals and challenges.'], ['Benchmark', 'Your data is compared with industry reference points.'], ['Model', 'Scenarios and forecasts are built and tested.'], ['Roadmap', 'Priorities and phases are agreed with you.'], ['Implement & review', 'We help roll out the plan and keep it on track.']],
                ['More confident, evidence-based decisions', 'Faster identification of opportunities', 'Time saved on routine analysis', 'Safeguards for ethical and compliant AI use'],
                [['How is this different from traditional consulting?', 'It adds data and predictive analysis to expert advice, so recommendations are tested before you commit.'], ['Is it only for large companies?', 'No. We scale the engagement to your data and decisions.'], ['How do you handle ethics and compliance?', 'We review models for bias and data protection issues before they influence decisions.']]),
        ];

        $out = [];
        foreach ($list as $s) $out[$s['slug']] = $s;
        return $out;
    }
}
