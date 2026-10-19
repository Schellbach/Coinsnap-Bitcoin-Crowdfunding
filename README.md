# Coinsnap Bitcoin Crowdfunding for WordPress

**Bitcoin crowdfunding plugin for WordPress — create funding campaigns and collect Bitcoin and Lightning contributions with Coinsnap or your own BTCPay Server.**

Coinsnap Bitcoin Crowdfunding lets WordPress site owners create goal-based Bitcoin crowdfunding campaigns directly on their own website.

It is designed for personal projects, group collections, gifts, events, trips, community initiatives and other shared funding goals. Supporters can contribute with **Bitcoin via Lightning or on-chain**, while campaign owners keep control of their WordPress content, campaign data and payment destination.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

## Key features

- Accept **Bitcoin and Lightning contributions**
- Support for **Lightning and Bitcoin on-chain**
- Create and manage **multiple crowdfunding campaigns**
- Set a **funding goal** for each campaign
- Add a campaign description and custom title
- Configure **three preset contribution amounts**
- Let supporters switch between supported display currencies
- Automatic currency conversion display
- Optional collection of donor information
- Optional public **shoutouts / supporter messages**
- Separate shoutout-list shortcode
- Light and dark themes
- Custom button text and thank-you message
- Individual campaign shortcodes
- Support for **Coinsnap** and **BTCPay Server**
- Payment-provider connection checks
- **Non-custodial / self-custody** payment model
- Optional bank settlement through supported Coinsnap partners

## Current version and requirements

Current plugin version: **1.2.1**

- Tested with WordPress **6.9.x**
- PHP **8.0 or newer**
- A **Coinsnap merchant account**, or
- Your own **BTCPay Server**

For settlement with Coinsnap, you can use a compatible Bitcoin or Lightning wallet. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/).

## Typical use cases

Coinsnap Bitcoin Crowdfunding can be used for:

- wedding or birthday gifts
- group trips and shared expenses
- community or neighborhood projects
- sports-team or club equipment
- events and workshops
- creative projects
- personal funding goals
- other private or shared initiatives

## How it works

1. Install and activate the plugin.
2. Open **Coinsnap Bitcoin Crowdfunding → Settings**.
3. Connect **Coinsnap** or your own **BTCPay Server**.
4. Choose a light or dark form theme.
5. Create a new crowdfunding campaign.
6. Set the campaign title, description and funding goal.
7. Configure contribution amounts, currency, donor fields and optional shoutouts.
8. Copy the generated shortcode into a page or post.
9. Make a test payment before sharing the campaign.

## Campaign configuration

Each crowdfunding campaign can be configured independently.

Available settings include:

- Campaign title
- Description
- Funding goal in satoshis
- Default currency
- Three preset contribution values
- Thank-you message
- Donor-information fields
- Optional custom donor field
- Optional shoutouts
- Campaign shortcode

You can run multiple campaigns at the same time and embed them on different WordPress pages.

## Shortcodes

Each crowdfunding campaign uses the main shortcode:

`[coinsnap_bitcoin_crowdfunding]`

The plugin associates the rendered form with the configured crowdfunding campaign.

For public supporter messages, the plugin also provides:

`[coinsnap_bitcoin_crowdfunding_shoutout]`

The exact shortcode shown in the WordPress backend should be used for the campaign or shoutout list you want to publish.

## Coinsnap or BTCPay Server

The plugin supports two payment-provider modes:

- **Coinsnap** — for users who want to accept Bitcoin and Lightning without operating their own payment infrastructure.
- **BTCPay Server** — for users who want to connect their own self-hosted BTCPay Server.

## Currency support

The plugin supports multiple Coinsnap currencies and allows supporters to switch the displayed contribution currency on the frontend.

The payment itself is completed in Bitcoin via Lightning or on-chain.

## Donor information and shoutouts

Campaign owners can optionally request donor information such as name or email.

Shoutouts can also be enabled so supporters can leave a public message together with their contribution. These messages can be displayed in a separate shoutout list on the site.

Only collect personal information when it is actually required for your use case and when you have an appropriate legal basis.

## Settlement

With Coinsnap, contributions can be settled to a compatible Bitcoin or Lightning wallet.

Coinsnap follows a **non-custodial / self-custody** approach and does not hold merchant Bitcoin.

Depending on the merchant setup and supported partner integrations, Bitcoin payments can also be converted and settled to a bank account.

## Installation

### Install from WordPress.org

1. In WordPress, go to **Plugins → Add New**.
2. Search for **Coinsnap Bitcoin Crowdfunding**.
3. Click **Install Now** and activate the plugin.
4. Open **Coinsnap Bitcoin Crowdfunding → Settings**.
5. Connect Coinsnap or BTCPay Server.
6. Create a crowdfunding campaign.
7. Copy the campaign shortcode to a page or post.
8. Make a test payment.

### Install from GitHub

1. Download this repository as a ZIP archive.
2. Upload it through **Plugins → Add New → Upload Plugin**.
3. Install and activate the plugin.
4. Configure the payment provider and create your campaign.

## Resources

- [Coinsnap Bitcoin Crowdfunding product page](https://coinsnap.io/wp-plugins/wp-bitcoin-crowdfunding/)
- [Bitcoin Crowdfunding User Guide](https://coinsnap.io/user-guides/wp-bitcoin-crowdfunding-user-guide/)
- [WordPress Plugin Directory](https://wordpress.org/plugins/coinsnap-bitcoin-crowdfunding/)
- [Demo](https://crowdfunding.coinsnap.org/)
- [Coinsnap Wallet](https://coinsnap.io/wallet/)
- [Coinsnap API documentation](https://docs.coinsnap.io/)
- [Coinsnap merchant account](https://app.coinsnap.io/register)
- [Telegram community and support](https://t.me/coinsnap)

> **Brand note:** Coinsnap is Bitcoin payment software for merchants and is not the provider of the similarly named coin identifier app "CoinSnap".

## Frequently Asked Questions

### Can supporters pay with both Lightning and Bitcoin on-chain?

Yes. The plugin supports contributions via both Lightning and Bitcoin on-chain.

### Can I use my own BTCPay Server?

Yes. BTCPay Server is supported as an alternative to Coinsnap.

### Can I create multiple crowdfunding campaigns?

Yes. You can create and manage multiple campaigns and embed them on different WordPress pages.

### Can I set a funding goal?

Yes. Each crowdfunding campaign can have its own funding goal.

### Can I offer preset contribution amounts?

Yes. Each campaign can define three preset contribution values.

### Can I collect donor information?

Yes. Donor information is optional and can be enabled when needed.

### Can supporters leave public messages?

Yes. Shoutouts can be enabled and displayed in a separate public list.

### Can supporters choose a display currency?

Yes. The current plugin supports multiple currencies and frontend currency switching.

### Do supporters need a Coinsnap account?

No. Supporters only need a compatible Bitcoin or Lightning wallet.

### Do I need my own Lightning node?

No. When using Coinsnap, no own Lightning node or Bitcoin payment server is required.

### Does Coinsnap hold my Bitcoin?

No. Coinsnap follows a non-custodial / self-custody approach and does not hold merchant Bitcoin.

## Changelog

#### 1.0.0 :: 2025-06-19
* Initial release.

#### 1.1.0 :: 2025-09-14
* Update: Added payment gateway client class. 
* Update: Added support for all the Coinsnap currencies instead of SATS only
* Update: Prevented redirect to payment gateway if payment amount is less than 1 SAT or currency in not supported by Coinsnap.
* Update: Prevented redirect to BTCPay server if payment amount is less than 0.000005869 BTC (0.50 EUR) for onchain payments, 0.000001 BTC (1 SAT) for Lightning payment or currency is not supported.
* Update: Minimum order amount is added to connection status notice.
* Fixed: Donation amount can be saved in SATS

#### 1.1.1 :: 2025-12-06
* Fixed Wordpress Plugin Check warnings.
* Compatibility with Wordpress 6.9 is tested.

#### 1.2.0 :: 2026-02-17
* Update: Switching between currencies on frontend.
* Update: Currency exchange rate calculation and display.
* Update: Donor's form interface.
* Compatibility with Wordpress 6.9.1 is tested.

#### 1.2.1 :: 2026-10-19
* Security: The payment webhook secret is kept on the server. It is no longer returned to visitors, and webhook registration is limited to administrators.
* Security: A payment notification is recorded only when its signature matches and the payment provider confirms that the invoice is settled.
* Security: Existing webhook secrets are regenerated on upgrade. Open the plugin settings and click Check so Coinsnap or BTCPay Server receives the new secret.

