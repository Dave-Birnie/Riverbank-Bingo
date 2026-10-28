# Bingo Website Production Checklist

**Status:** Planning | **Hosting:** Existing Hostinger Business account (verify compatibility) | **Launch:** Temporary staging site, then production

## Website pages and content
- [ ] Complete homepage with animated Flashboard and minor visual refinements.
- [ ] Complete About Us page and client business details.
- [ ] Complete How to Play page.
- [ ] Complete Game Schedule page.
- [ ] Complete Watch Live page with embedded broadcast and offline message.
- [ ] Complete Card Distributors page with interactive Google Map pins.
- [ ] Complete Winners page.
- [ ] Complete Blog page and individual posts.
- [ ] Complete Contact Us page.
- [ ] Add Become a Distributor page and link in footer.
- [ ] Add Privacy Policy page.
- [ ] Add Rules page.
- [ ] Ensure owner can edit existing pages in CMS, but cannot create arbitrary new pages.

## CMS: owner administration
- [ ] Provide one owner/admin login and standard editing screens.
- [ ] Build dashboard showing next game, jackpot, announcements, and stream status.
- [ ] Manage games, recurring schedules, start/end times, and prize/jackpot details.
- [ ] Provide reusable game templates for weekly schedules.
- [ ] Manage distributor locations and map pin details.
- [ ] Manage site-wide scrolling announcement banner with custom text.
- [ ] Show cancellation notices over affected games.
- [ ] Manage special events and other announcements.
- [ ] Manage winners and winner stories.
- [ ] Edit About, How to Play, Rules, Privacy, Become a Distributor, and other existing page content.
- [ ] Edit business phone number and email once in settings and reflect changes site-wide.
- [ ] Manage livestream provider URL/embed and offline message.
- [ ] Provide blog post editor with create/edit/publish controls.
- [ ] Provide media library for images and logos.
- [ ] Allow scheduled publishing for blog posts and announcements.
- [ ] Provide preview before publishing.
- [ ] Maintain activity/change history and content rollback capability.

## Live game mode
- [ ] Add prominent **Go Live** and **End Live** controls to CMS.
- [ ] Automatically start live mode at scheduled game start time.
- [ ] Automatically end live mode at scheduled game end time.
- [ ] Allow manual overrides of both start and end.
- [ ] While live, replace homepage Flashboard hero with **Our game is currently in session**, a Live Now indicator, and **Watch Live** link.
- [ ] Restore normal animated Flashboard hero after live mode ends.
- [ ] Make game status consistent across homepage, schedule, and Watch Live page.

## Livestreaming
- [ ] Select livestream provider that explicitly permits the hall's bingo broadcasts; obtain written confirmation of permitted use.
- [ ] Confirm broadcasting arrangements comply with applicable Ontario gaming authorization and rules.
- [ ] Support watching games for cards purchased elsewhere; no online card purchasing at launch.
- [ ] Embed stream on Watch Live page and let owner change URL/embed in CMS.
- [ ] Provide clear offline/unavailable-stream state.
- [ ] Test broadcast playback and monitoring before launch.

## Future paid AI CMS assistant (plan now, disabled at launch)
- [ ] Structure CMS actions and permissions so a future chatbot can safely use the same editing capabilities.
- [ ] Plan a persistent bottom-corner chatbot available across admin pages.
- [ ] Support conversational edits to all permitted CMS content, including games, prizes, schedules, announcements, distributors, settings, and pages.
- [ ] Support drafting, creating, and editing blog posts through chat.
- [ ] Require confirmation before chatbot changes are published.
- [ ] Keep chatbot feature disabled until purchased/activated.

## Hosting, security, and maintenance
- [ ] Verify Hostinger Business plan compatibility with chosen site framework, CMS, database, and integrations.
- [ ] Prefer low-maintenance managed services where needed; avoid manual server administration.
- [ ] Configure domain, HTTPS/SSL, and secure owner login.
- [ ] Configure automatic site/database backups and test recovery.
- [ ] Developer handles backup restores; no owner self-service restore required.
- [ ] Configure security protections and automatic updates where supported.
- [ ] Configure website uptime and stream/embed failure alerts to developer only.
- [ ] Verify access control, input validation, and protection of CMS credentials and API keys.
- [ ] Confirm Google Maps API configuration and any usage costs.

## Staging, testing, and launch
- [ ] Deploy to temporary staging site for soft launch and owner review.
- [ ] Verify every public page and CMS editing workflow.
- [ ] Test mobile layout, accessibility, navigation, links, and forms.
- [ ] Test distributor map pins and contact information.
- [ ] Test scheduled and manual live mode, automatic end, and hero transitions.
- [ ] Test stream playback, offline messages, and failure alerts.
- [ ] Test announcement banner, cancellations, scheduled posts, previews, and change history.
- [ ] Test backups and developer-led restoration.
- [ ] Confirm final client content and approval on staging.
- [ ] Configure production domain and deploy approved version.
- [ ] Verify production SSL, CMS, forms, map, stream, scheduled jobs, and alerts.
- [ ] Monitor site after launch and resolve defects.

## Future phase: online card sales (not included in launch)
- [ ] Assess regulatory requirements and authorization before implementing online purchases.
- [ ] Plan online purchasing with printable bingo cards.
- [ ] Define payment, card issuance, fraud prevention, and customer support requirements when future phase is approved.
