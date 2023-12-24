# smtpapi

This project is just a game for me, you know, when the sun sets too early and you don't want to watch TV

This is a Web Api to send mail from another one

You could own a webserver that has to send some mails like "password reinit"
Your server could be black listed for spamming or your server could be in a ip's class that is banned for spamming (look at UCEPROTECT3)

So what is the solution ?
SMTP Relay ? ==> not simple !
Sending mail over ipv6 ? ==> wich FAI is ipv6 compatible , in France ? None !
Pay for sending mail ? ==> never ! 
Pay for white listing ? ==> never !
Pay for unbanning ? ==> never !

So
If you own another webserver that is not banned it could send mails for the first one.
How ?
The first server call a web api that is running on the second one. That's all.

This api is very simple : no java, no javascript : just php and sqlite3 you can use it with apache , nginx, lighttp
There is a little database using sqlite that is just here to store messages that have been prepared or sent.

This web API is little protected by user and password that is in the TBPARAM table.
This just an exemple , the password is not encrypted.
YOU HAVE TO AJUST THAT IF YOU NEED TO.

So this api lets you :
- create mail
- set recipients list
- set black carbon copy recipitents list
- set attached files list
- send the message
- store the prepared message and the result of sending

You can youse whatever smtp server/login/password with whatever port, security
You can ask for acknowledgement

It's using phpmailer, pdo, sqlite3, and php
For information I'm using php 8.2

First you have to create a mail (new method)
If you don't already do it : with the putcc method, you can change the recipients
If you don't already do it : with the putbcc method, you can change the bcc list
Then you can add attached files one by one with the addfile method
And finally you can send the message with the send method
The database contain informations that lets you know all about the prepared (and / or sent) messages.

Each time you call this api, you have to give username and password that are stored in TBPARAM table (default is admin / password)

There are 2 scripts :
- index.php is the api
- test.php is a test script that can call index.php 
And 3 folders :
- phpmailer contains minimum files for phpmailer
- db contains the database file and a backup in sql format of the database structure.
- tmp : it's a temporary folder, apache or nginx have to write temporary files here (to received the updload files that will become the attached files)

Remember :
This is just a basic solution.
Don't attached very big files
sqlite is not the good solution for storing big blob data and php upload is not the best solution to send big files.
But for sending automatic mails like password reinitialization, mail validation, basics reports : it's the simpliest one.

I use it to send messages that have no more than 8 attached files with each one is no more than 1 Mo since 5 years.
(That's Enough for what I need)
