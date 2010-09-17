# Introduction to PHPMailUnit.

PHPMailUnit lets you test the sending of emails and check all the related data
of every email.

PHPMailUnit implements under the covers a basic SMTP server and instead of
delivering the email it logs it in a file. This file is then accessed by PHPMailUnit
to provide every data of the email which you can assert in your unit tests.

# Using PHPMailUnit

Quite easy indeed.

1. Be sure to add PHPMailUnit directory to the `include_path`. Then in your test...

2. Call `PHPMailUnit::setUp();`. This will start the smtp server.

3. Send an email with following smtp server settings: `localhost:3535`

4. Retrieve the email sent using `PHPMailUnit::getLastMail();`

5. Call `PHPMailUnit::tearDown();` to stop the internal smtp server

# How to contribute

I did this project mainly to fit my own needs it can certainly be improved. If
you want to use it but face a wall either fill an issue (or even better fork the
project and send a pull request) or contact me by email (jacqueminv at gmail dot com)