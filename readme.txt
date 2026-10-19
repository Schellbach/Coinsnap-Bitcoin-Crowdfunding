=== Coinsnap Bitcoin Crowdfunding ===

Contributors: coinsnap
Tags: bitcoin, lightning, crowdfunding, fundraising, btcpay
Tested up to: 6.9.4
Requires PHP: 8.0
Stable tag: 1.2.1
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create Bitcoin crowdfunding campaigns on WordPress and collect Lightning or on-chain contributions with Coinsnap or your own BTCPay Server.

== Description ==

= Bitcoin crowdfunding directly on your WordPress website =

Coinsnap Bitcoin Crowdfunding lets you create goal-based crowdfunding campaigns directly on WordPress.

It is designed for personal projects, group collections, gifts, events, trips, community initiatives and other shared funding goals. Supporters can contribute with **Bitcoin via Lightning or on-chain** while you keep control of your WordPress content, campaign data and payment destination.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

= Key features =

* Accept **Bitcoin and Lightning contributions**
* Support for **Lightning and Bitcoin on-chain**
* Create and manage **multiple crowdfunding campaigns**
* Set a **funding goal** for each campaign
* Add a campaign description and custom title
* Configure **three preset contribution amounts**
* Multiple supported display currencies
* Frontend currency switching and conversion display
* Optional donor information
* Optional public **shoutouts / supporter messages**
* Separate shoutout-list shortcode
* Light and dark themes
* Custom button text and thank-you message
* Individual campaign shortcodes
* Support for **Coinsnap** and **BTCPay Server**
* Non-custodial / self-custody payment model
* Optional bank settlement through supported Coinsnap partners

= Typical use cases =

Use the plugin for:

* wedding or birthday gifts
* group trips and shared expenses
* community or neighborhood projects
* sports-team or club equipment
* events and workshops
* creative projects
* personal or shared funding goals

= How it works =

1. Install and activate the plugin.
2. Open **Coinsnap Bitcoin Crowdfunding → Settings**.
3. Connect Coinsnap or your own BTCPay Server.
4. Choose the form theme.
5. Create a crowdfunding campaign.
6. Set the title, description and funding goal.
7. Configure contribution amounts, currency, donor information and optional shoutouts.
8. Copy the generated shortcode into a page or post.
9. Make a test contribution.

= Campaign settings =

Each campaign can include:

* Campaign title
* Description
* Funding goal in satoshis
* Default currency
* Three preset contribution amounts
* Thank-you message
* Optional donor-information fields
* Optional custom donor field
* Optional shoutouts
* Campaign shortcode

You can run multiple crowdfunding campaigns at the same time.

= Payment providers =

The plugin supports:

* **Coinsnap** — accept Bitcoin and Lightning without operating your own payment infrastructure.
* **BTCPay Server** — connect your own self-hosted BTCPay Server.

= Currency support =

The plugin supports multiple Coinsnap currencies and lets supporters switch the displayed contribution currency on the frontend.

Payments are completed in Bitcoin via Lightning or on-chain.

= Donor information and shoutouts =

Donor information is optional.

You can also enable shoutouts so supporters can leave a public message together with their contribution. Shoutouts can be displayed in a separate list on your website.

Only collect personal information when needed and when you have an appropriate legal basis.

= Settlement =

With Coinsnap, contributions can be settled to a compatible Bitcoin or Lightning wallet.

One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

Depending on the merchant setup and supported partner integrations, Bitcoin payments can also be converted and settled to a bank account.

= More information =

* Product page: https://coinsnap.io/wp-plugins/wp-bitcoin-crowdfunding/
* User Guide: https://coinsnap.io/user-guides/wp-bitcoin-crowdfunding-user-guide/
* WordPress Plugin Directory: https://wordpress.org/plugins/coinsnap-bitcoin-crowdfunding/
* Demo: https://crowdfunding.coinsnap.org/
* GitHub: https://github.com/Coinsnap/Coinsnap-Bitcoin-Crowdfunding
* Coinsnap Wallet: https://coinsnap.io/wallet/
* Coinsnap API: https://docs.coinsnap.io/
* Coinsnap account: https://app.coinsnap.io/register

== Installation ==

1. In WordPress, go to **Plugins → Add New**.
2. Search for **Coinsnap Bitcoin Crowdfunding**.
3. Click **Install Now** and activate the plugin.
4. Go to **Coinsnap Bitcoin Crowdfunding → Settings**.
5. Select Coinsnap or BTCPay Server and connect the payment provider.
6. Go to **Crowdfundings** and create a campaign.
7. Configure the funding goal, currency, contribution amounts and optional donor fields.
8. Copy the generated shortcode to a page or post.
9. Make a test payment.

== Frequently Asked Questions ==

= Can supporters pay with both Lightning and Bitcoin on-chain? =

Yes. The plugin supports both Lightning and Bitcoin on-chain contributions.

= Can I use my own BTCPay Server? =

Yes. BTCPay Server is supported as an alternative to Coinsnap.

= Can I create multiple crowdfunding campaigns? =

Yes. Multiple campaigns can run simultaneously and be embedded on different pages.

= Can I set a funding goal? =

Yes. Each campaign can have its own funding goal.

= Can I offer preset contribution amounts? =

Yes. Each campaign can define three preset contribution values.

= Can I collect donor information? =

Yes. Donor information is optional and can be enabled per campaign.

= Can supporters leave public messages? =

Yes. Shoutouts can be enabled and displayed in a separate list.

= Can supporters choose a display currency? =

Yes. The current plugin supports frontend currency switching.

= Do supporters need a Coinsnap account? =

No. Supporters only need a compatible Bitcoin or Lightning wallet.

= Do I need my own Lightning node? =

No. When using Coinsnap, no own Lightning node or Bitcoin payment server is required.

= Does Coinsnap hold my Bitcoin? =

No. Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

== Screenshots ==

1. Install Coinsnap Bitcoin Crowdfunding from the WordPress Plugin Directory
2. Coinsnap payment-provider settings
3. BTCPay Server payment-provider settings
4. Crowdfunding campaigns list
5. Crowdfunding campaign configuration
6. Funding goal and preset contribution amounts
7. Optional donor-information settings
8. Shoutout configuration
9. Bitcoin crowdfunding form on the website
10. Lightning and Bitcoin payment QR code

=== Changelog ===

= 1.0.0 :: 2025-06-19 =
* Initial release.

= 1.1.0 :: 2025-09-14 =
* Update: Added payment gateway client class. 
* Update: Added support for all the Coinsnap currencies instead of SATS only
* Update: Prevented redirect to payment gateway if payment amount is less than 1 SAT or currency in not supported by Coinsnap.
* Update: Prevented redirect to BTCPay server if payment amount is less than 0.000005869 BTC (0.50 EUR) for onchain payments, 0.000001 BTC (1 SAT) for Lightning payment or currency is not supported.
* Update: Minimum order amount is added to connection status notice.
* Fixed: Donation amount can be saved in SATS

= 1.1.1 :: 2025-12-06 =
* Fixed Wordpress Plugin Check warnings.
* Compatibility with Wordpress 6.9 is tested.

= 1.2.0 :: 2026-02-17 =
* Update: Switching between currencies on frontend.
* Update: Currency exchange rate calculation and display.
* Update: Donor's form interface.
* Compatibility with Wordpress 6.9.1 is tested.

= 1.2.1 :: 2026-10-19 =
* Security: The payment webhook secret is kept on the server. It is no longer returned to visitors, and webhook registration is limited to administrators.
* Security: A payment notification is recorded only when its signature matches and the payment provider confirms that the invoice is settled.
* Security: Existing webhook secrets are regenerated on upgrade. Open the plugin settings and click Check so Coinsnap or BTCPay Server receives the new secret.