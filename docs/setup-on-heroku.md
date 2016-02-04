#Setup on Heroku

###Heroku buildpack
Set multiple buildpacks.
```shell
heroku buildpacks:set https://github.com/ddollar/heroku-buildpack-multi.git
```

###Cron
```shell
heroku addons:add scheduler
```
Set every 10 min.  
```shell
$ php -r "echo file_get_contents('http://yourdomain.com/cron.php');"
```
Only one cronjob is needed for all installed domains (unlike eLAS). Just choose one domain.

###Domain
Configure your domain with a CNAME to the Heroku app URL.
set a config var for each domain to the name of the schema in the database
```shell
heroku config:set SCHEMA_EXAMPLE__COM=examplecom
```
The environment variable SCHEMA_domain: couples a domain to a schema

* Dots in domain are replaced by double underscore __
* Hyphens in domain are replaced by triple underscore ___
* Colons in domain are replaced by quadruple underscore ____
* all characters should be uppercase in the environment variable.

i.e couple e-example.com with schema `eexample`
```shell
	heroku config:set SCHEMA_E___EXAMPLE__COM=eexample
```
Also add the domain to Heroku:
```shell
heroku domains:add e.example.com
```

i.e localhost:40000 on php development server
```shell
	SCHEMA_LOCALHOST____40000=abc (define here other environment variables like DATABASE_URL) php -d variables_order=EGPCS -S localhost:40000
```

The schema name is also:
  * the name of the session
  * prefix of the files in S3 cloud storage
  * prefix of the keys in Redis.

By convention the schema is named after the so called system tag or letscode of the letsgroup.


###AWS S3
Create a IAM user on AWS with access only to S3. Then create 3 buckets in your region for images, documents and 3th party (javascript + css) libraries.
See (file inludes/defaults)[includes/inc_default.php] for which libraries are to be uploaded. 
The buckets should have the same name as the url.

```shell
heroku config:set S3_IMG=img.letsa.net S3_DOC=doc.letsa.net S3_RES=res.letsa.net
heroku config:set AWS_ACCESS_KEY_ID=aaa AWS_SECRET_ACCESS_KEY=bbb
```

Create CNAME records to these buckets

img.letsa.net CNAME record for img.letsa.net.s3-eu-central-1.amazonaws.com

See [the AWS S3 docs](http://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html)

You need to set up CORS configuration on bucket S3_RES for the fonts of footable 2.0.3 to load.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
    <CORSRule>
        <AllowedOrigin>*</AllowedOrigin>
        <AllowedMethod>GET</AllowedMethod>
        <MaxAgeSeconds>3000</MaxAgeSeconds>
        <AllowedHeader>Authorization</AllowedHeader>
    </CORSRule>
</CORSConfiguration>
```

###Redis cloud
```shell
heroku addons:add rediscloud:30
```

###Mandrill
```shell
heroku addons:add mandrill
```

###Mongolab (logs, forum topics and document references are stored in mongodb)
```shell
heroku addons:add mongolab
```

###Other environment vars

* TIMEZONE: defaults to 'Europe/Brussels'
* MASTER_PASSWORD: sha512 encoded password for 'master' -> gives admin access to all letsgroups.

CDN urls of cdns see [includes/inc_default.php] for defaults