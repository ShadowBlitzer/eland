##Development server

####Web process

There are some issues with heroku-buildpack-php for [local execution for Debian & Ubuntu](https://github.com/heroku/heroku-buildpack-php/issues/55), so we are using the PHP
development server for local testing. 
Put the environment variables in an .env file at the root of the project in the form of 
KEY=VALUE on every new line. 
Then, at the root of the project, you can start the development server with ./serve 
If everything goes alright the local site is served at localhost:8888
Optionally, you can give another location as argument for ./serve, like i.e.
localhost:40000 
You can also create a .local file with on the first line the location. 

####Background processes

The background processes DO work with the heroku local command (install heroku-cli).
The processes are:
* worker: all kinds of slow processes (cleanup, checking for notifications, ...)
* mail: sending a mails from the queue spread evenly in time
* sync: for groups/currency that come from eLAS, a copy of their eLAS database is kept parallel in sync with the eLAND database (the primary source) for backward compatibility. The sync process also loads the entire eLAS database into the eLAND database in case of a new group 
with a eLAS database.

One process can be run: 
´´´shell
heroku local worker
´´´

Or, all of them together:
´´´shell
heroku local worker,mail,sync
´´´
