# kebabble
## Food order listing powered by WordPress, integrated with Slack.

![Kebabble Man](https://www.soupbowl.io/wp-content/uploads/2018/04/kebabbleman.png)

Personal project to learn more about both WordPress and Slack APIs. The actual
usage of this project is debatable, but is mostly intended as an educational
project anyway.

## Requirements
* PHP 7+ (Pending 5.6 test).
* WordPress 4+.
* Advanced Custom Fields.
* Slack (admin needed for setup).

## Installation
Grab a build zip, and use WordPress plugin 'add new' to add to your WordPress.

Alternatively, if you wish to compile the project yourself, you will need to
use Composer. Use `composer install` to set up the project dependencies.

Optionally, you can grab a copy of the advanced-custom-fields plugin, and
store it in the root directory of the project. Otherwise, you can instll it
seperately in your WordPress installation.

## Usage
For the bot to integrate with slack, it needs to be given a Bot user and
permissions to use it. You can [modify the settings here][1].

If you haven't done so before, create a new app and call it whateveer you
want (Kebabble, for example!) and allocate it to the a workspace. 

Once in the app configuration, enable it to use 'Bots' and 'Permissions'.
If done in that order, 'Permissions' will give you a 
`Bot User OAuth Access Token`. Copy this key and paste it in the relevant
setting within Kebabble admin in WordPress, alongside the channel of choice
you wish to operate on. Give it a quick test and you should be good to go.



[1]: https://api.slack.com/apps
