PHP Gearman Integration.

This is software package which basically works as a Queue System and queue data will be processd within the Gearman System.
In this system, I have integrated AWS SQS service with Gearman.

Workflow:
Step 1: Data will be fetched from AWS SQS(Can be use any other stores system).
Step 2: Fetched data will be queued in Gearman queue by Gearman Client.
Step 3: Gearman Server will find the appropriate Worker to process the queue item.
Step 4: Gearman Worker will process the data.


Install PHP package.
Install Gearman for php. Note it may different for PHP versions.

Enable PHP GD library http://www.php.net/manual/en/mbstring.installation.php
