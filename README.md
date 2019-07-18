# kebabble
## Food order listing powered by WordPress, integrated with Slack.

![Kebabble Man][KM]

Personal project to learn more about both WordPress and Slack APIs. The actual
usage of this project is debatable, but is mostly intended as an educational
project anyway.

[![forthebadge][F1]][FU]
[![forthebadge][F2]][FU]
[![forthebadge][F3]][FU]

![Travis status](https://api.travis-ci.org/soup-bowl/kebabble.svg?branch=ci-dev)

## Requirements
* PHP 7.2 or higher.
* WordPress 4 or higher.
* Slack (admin needed for setup).

## Installation
[Visit the tags section][2] and click 'Plugin Download' on the latest release, 
and use WordPress plugin 'add new' to add to your WordPress.

Alternatively, if you wish to compile the project yourself, you will need to
use Composer. Use `composer install` to set up the project dependencies.

## Usage
For the bot to integrate with slack, it needs to be given a Bot user and
permissions to use it. You can [modify the settings here][1].

If you haven't done so before, create a new app and call it whatever you want 
(Kebabble, for example!) and allocate it to the a workspace. 

Once in the app configuration, enable it to use 'Bots' and 'Permissions'. If 
done in that order, 'Permissions' will give you a `Bot User OAuth Access Token`. 
Copy this key and paste it in the relevant setting within Kebabble admin in 
WordPress, alongside the channel of choice you wish to operate on. Give it a 
quick test and you should be good to go.

### Mentions (optional)
For mentions, you will need to also register the API endpoint with the Slack bot 
[Events API][3]. On the app configuration page, click 'Event Subscriptions' on 
the left-hand side, and enable. The endpoint is (don't forget to substitute the 
site URL):

`<Your WordPress site URL>/index.php?rest_route=/kebabble/v1/slack`

And once verified, under 'Subscribe to Bot Events' add `app_mention`. To verify,
add Kebabble to a channel and say `@kebabble hello world` and it should respond 
to you.

[1]: https://api.slack.com/apps
[2]: https://gitlab.com/soup-bowl/kebabble/tags
[3]: https://api.slack.com/events-api

[KM]: https://www.soupbowl.io/wp-content/uploads/2018/04/kebabbleman.png

[FU]: https://forthebadge.com
[F1]: https://forthebadge.com/images/badges/gluten-free.svg
[F2]: https://forthebadge.com/images/badges/built-with-grammas-recipe.svg
[F3]: https://forthebadge.com/images/badges/compatibility-club-penguin.svg