magento-oauth-example-php
=========================

```
git clone https://github.com/nickdufresne/magento-oauth-example-php.git
cd magento-oauth-example-php

heroku create
heroku config:add BASE_URL=https://magento.domain

# see http://devdocs.magento.com/guides/m1x/api/rest/authentication/oauth_configuration.html
# for setting up consumer app key and secret
# set the callback url to: https://xxx-yyy-123.herokuapp.com/ (your heroku domain)

heroku config:add CONSUMER_KEY=...
heroku config:add CONSUMER_SECRET=...
heroku config:add CALLBACK_URL=https://xxx-yyy-123.herokuapp.com/

git push heroku master
```
