### Post Revolut Business Tranactions into Slack and Freeagent

This was written as quick stop gap (and therefore is messy) while Revolut build out more integrations, **use at your own risk.** 

#### Example
![Example](http://share.agnew.co/qswXmO+)

#### Setup

It's wirtten in Laravel (PHP)

1. Deploy to server, a small DigitalOcean server would work fine.
2. Setup your .env file for your database details

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xxxxx
DB_USERNAME=xxxx
DB_PASSWORD=xxxxx
```

3. Run `composer install` and `php artisan migrate`
4. Set up Revolut details in .env

```
REVOLUT_KEY=prod_xxx-xxxxxxxxxxxxxxxxxxxxxx
REVOLUT_DOMAIN=https://b2b.revolut.com
```

5. Set up Slack webhook in .env
```
SLACK_WEBHOOK=https://hooks.slack.com/services/XXXXXXX/YYYYYYYY
```

6. Set up Freeagent in .env (requires a freeagent app to set up)
```
FREEAGENT_SANDBOX=false
FREEAGENT_CLIENT_ID=xxxxxxx
FREEAGENT_CLIENT_SECRET=xxxxx
FREEAGENT_REDIRECT_URI=http://{server-ip}/freeagent/oauth
```
7. Do Oauth setup for Freeagent by visting `/freeagent/setup`, you should see a success messaage.

8. Now you need to map your Revolut bank account to a Freeagent bank account in the .env
```
REVOLUT_TO_FREEAGENT=xxx-xxxx-xxxx-xxx-xxx-xx:1111,xxx-xxxx-xxxx-xxx-xxx-xy:1112,xxx-xxxx-xxxx-xxx-xxx-yy:1113
```

9. Now set up Laravel cron jobs

````
* * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
````

10. You should be all done :) 
