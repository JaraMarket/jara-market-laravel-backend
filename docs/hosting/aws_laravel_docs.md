Title: Deploying a Laravel application to Elastic Beanstalk - AWS Elastic Beanstalk

Source: https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html

---

[View a markdown version of this page](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.md)
[Documentation](https://docs.aws.amazon.com/index.html)
[AWS Elastic Beanstalk](https://docs.aws.amazon.com/elastic-beanstalk/index.html)
[Developer Guide](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/Welcome.html)
[Prerequisites](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-prereqs)
[Launch an Elastic Beanstalk environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-launch)
[Install Laravel and generate a website](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-generate)
[Deploy your application](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-deploy)
[Configure Composer settings](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-configure)
[Add a database to your environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-database)
[Cleanup](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-cleanup)
[Next steps](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-nextsteps)

Laravel is an open source, model-view-controller (MVC) framework for PHP. This tutorial walks you through the process of generating a Laravel application, deploying it to an AWS Elastic Beanstalk environment, and configuring it to connect to an Amazon Relational Database Service (Amazon RDS) database instance.

###### Sections
- [Prerequisites](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-prereqs)
- [Launch an Elastic Beanstalk environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-launch)
- [Install Laravel and generate a website](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-generate)
- [Deploy your application](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-deploy)
- [Configure Composer settings](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-configure)
- [Add a database to your environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-database)
- [Cleanup](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-cleanup)
- [Next steps](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-nextsteps)
[Prerequisites](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-prereqs)
[Launch an Elastic Beanstalk environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-launch)
[Install Laravel and generate a website](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-generate)
[Deploy your application](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-deploy)
[Configure Composer settings](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-configure)
[Add a database to your environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-database)
[Cleanup](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-cleanup)
[Next steps](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-laravel-tutorial.html#php-laravel-tutorial-nextsteps)

This tutorial assumes you have knowledge of the basic Elastic Beanstalk operations and the Elastic Beanstalk console. If you haven't already, follow the instructions in [Learn how to get started with Elastic Beanstalk](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/GettingStarted.html) to launch your first Elastic Beanstalk environment.
To follow the procedures in this guide, you will need a command line terminal or shell to run commands. Commands are shown in listings preceded by a prompt symbol ($) and the name of the current directory, when appropriate.

```
~/eb-project$ this is a command this is output
```


```
~/eb-project$ this is a command this is output
```


```
this is a command
```

On Linux and macOS, you can use your preferred shell and package manager. On Windows you can [install the Windows Subsystem for Linux](https://docs.microsoft.com/en-us/windows/wsl/install-win10) to get a Windows-integrated version of Ubuntu and Bash.
Laravel 6 requires PHP 7.2 or later. It also requires the PHP extensions listed in the [server requirements](https://laravel.com/docs/6.x/installation#server-requirements) topic in the official Laravel documentation. Follow the instructions to install PHP and Composer.
For Laravel support and maintenance information, see the [support policy](https://laravel.com/docs/master/releases#support-policy) topic on the official Laravel documentation.

Use the Elastic Beanstalk console to create an Elastic Beanstalk environment. Choose the PHP platform and accept the default settings and sample code.

###### To launch an environment (console)
1. 
    Open the Elastic Beanstalk console using this preconfigured link: [console.aws.amazon.com/elasticbeanstalk/home#/newApplication?applicationName=tutorials&environmentType=LoadBalanced](https://console.aws.amazon.com/elasticbeanstalk/home#/newApplication?applicationName=tutorials&environmentType=LoadBalanced)

2. 
    For Platform, select the platform and platform branch that match the language used by your application.

3. 
    For Application code, choose Sample application.

4. 
    Choose Review and launch.

5. 
    Review the available options. Choose the available option you want to use, and when you're ready, choose Create app.

Open the Elastic Beanstalk console using this preconfigured link: [console.aws.amazon.com/elasticbeanstalk/home#/newApplication?applicationName=tutorials&environmentType=LoadBalanced](https://console.aws.amazon.com/elasticbeanstalk/home#/newApplication?applicationName=tutorials&environmentType=LoadBalanced)
For Platform, select the platform and platform branch that match the language used by your application.
For Application code, choose Sample application.
Choose Review and launch.
Review the available options. Choose the available option you want to use, and when you're ready, choose Create app.
Environment creation takes about 5 minutes and creates the following resources:
- 
    EC2 instance – An Amazon Elastic Compute Cloud (Amazon EC2) virtual
      machine configured to run web apps on the platform that you choose.
    Each platform runs a specific set of software, configuration files, and scripts to support a specific language version, framework, web container, or
      combination of these. Most platforms use either Apache or NGINX as a reverse proxy that sits in front of your web app, forwards requests to it, serves
      static assets, and generates access and error logs.

- 
    Instance security group – An Amazon EC2 security group configured to allow inbound traffic on port 80. This
      resource lets HTTP traffic from the load balancer reach the EC2 instance running your web app. By default, traffic isn't allowed on other ports.

- 
    Load balancer – An Elastic Load Balancing load balancer configured to distribute requests to the instances running your
      application. A load balancer also eliminates the need to expose your instances directly to the internet.

- 
    Load balancer security group – An Amazon EC2 security group configured to allow inbound traffic on port 80. This
      resource lets HTTP traffic from the internet reach the load balancer. By default, traffic isn't allowed on other ports.

- 
    Auto Scaling group – An Auto Scaling group configured to replace
      an instance if it is terminated or becomes unavailable.

- 
    Amazon S3 bucket – A storage location for your source
      code, logs, and other artifacts that are created when you use Elastic Beanstalk.

- 
    Amazon CloudWatch alarms – Two CloudWatch alarms that monitor the load on the instances in your environment and that are
      triggered if the load is too high or too low. When an alarm is triggered, your Auto Scaling group scales up or down in response.

- 
    CloudFormation stack – Elastic Beanstalk uses CloudFormation to launch the
      resources in your environment and propagate configuration changes. The resources are defined
      in a template that you can view in the [CloudFormation
        console](https://console.aws.amazon.com/cloudformation).

- 
    Domain name – A domain name that routes to your
      web app in the form
          subdomain.region.elasticbeanstalk.com.
    Domain securityTo augment the security of your Elastic Beanstalk applications, the elasticbeanstalk.com domain is registered in the
      [Public Suffix List (PSL)](https://publicsuffix.org/).If you ever need to set sensitive cookies in the default domain name for your Elastic Beanstalk applications, we recommend that you use cookies with a
    __Host- prefix for increased security. This practice defends your domain against cross-site request forgery attempts (CSRF). For more
    information see the [Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#cookie_prefixes) page in the Mozilla
    Developer Network.

## Launch an Elastic Beanstalk environment
EC2 instance – An Amazon Elastic Compute Cloud (Amazon EC2) virtual machine configured to run web apps on the platform that you choose.
Each platform runs a specific set of software, configuration files, and scripts to support a specific language version, framework, web container, or combination of these. Most platforms use either Apache or NGINX as a reverse proxy that sits in front of your web app, forwards requests to it, serves static assets, and generates access and error logs.
Instance security group – An Amazon EC2 security group configured to allow inbound traffic on port 80. This resource lets HTTP traffic from the load balancer reach the EC2 instance running your web app. By default, traffic isn't allowed on other ports.
Load balancer – An Elastic Load Balancing load balancer configured to distribute requests to the instances running your application. A load balancer also eliminates the need to expose your instances directly to the internet.
Load balancer security group – An Amazon EC2 security group configured to allow inbound traffic on port 80. This resource lets HTTP traffic from the internet reach the load balancer. By default, traffic isn't allowed on other ports.
Auto Scaling group – An Auto Scaling group configured to replace an instance if it is terminated or becomes unavailable.
Amazon S3 bucket – A storage location for your source code, logs, and other artifacts that are created when you use Elastic Beanstalk.
Amazon CloudWatch alarms – Two CloudWatch alarms that monitor the load on the instances in your environment and that are triggered if the load is too high or too low. When an alarm is triggered, your Auto Scaling group scales up or down in response.
CloudFormation stack – Elastic Beanstalk uses CloudFormation to launch the resources in your environment and propagate configuration changes. The resources are defined in a template that you can view in the [CloudFormation console](https://console.aws.amazon.com/cloudformation).
Domain name – A domain name that routes to your web app in the form subdomain.region.elasticbeanstalk.com.

```
subdomain
```


```
region
```


###### Domain security
To augment the security of your Elastic Beanstalk applications, the elasticbeanstalk.com domain is registered in the [Public Suffix List (PSL)](https://publicsuffix.org/).
If you ever need to set sensitive cookies in the default domain name for your Elastic Beanstalk applications, we recommend that you use cookies with a __Host- prefix for increased security. This practice defends your domain against cross-site request forgery attempts (CSRF). For more information see the [Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#cookie_prefixes) page in the Mozilla Developer Network.

```
__Host-
```

All of these resources are managed by Elastic Beanstalk. When you terminate your environment, Elastic Beanstalk terminates all the resources that it contains.

###### Note
The Amazon S3 bucket that Elastic Beanstalk creates is shared between environments and is not deleted during environment termination. For more information, see [Using Elastic Beanstalk with Amazon S3](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/AWSHowTo.S3.html).

## Install Laravel and generate a website
Composer can install Laravel and create a working project with one command:

```
~$ composer create-project --prefer-dist laravel/laravel eb-laravel
```


```
~$ composer create-project --prefer-dist laravel/laravel eb-laravel
```


```
composer create-project --prefer-dist laravel/laravel eb-laravel
```

Composer installs Laravel and its dependencies, and generates a default project.
If you run into any issues installing Laravel, go to the installation topic in the official documentation: [https://laravel.com/docs/6.x](https://laravel.com/docs/6.x).

## Deploy your application
Create a [source bundle](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/applications-sourcebundle.html) containing the files created by Composer. The following command creates a source bundle named laravel-default.zip. It excludes files in the vendor folder, which take up a lot of space and are not necessary for deploying your application to Elastic Beanstalk.

```
laravel-default.zip
```


```
vendor
```


```
~/eb-laravel$ zip ../laravel-default.zip -r * .[^.]* -x "vendor/*"
```


```
~/eb-laravel$ zip ../laravel-default.zip -r * .[^.]* -x "vendor/*"
```


```
zip ../laravel-default.zip -r * .[^.]* -x "vendor/*"
```

Upload the source bundle to Elastic Beanstalk to deploy Laravel to your environment.

###### To deploy a source bundle
1. Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk),
  and in the Regions list, select your AWS Region.
2. 
  In the navigation pane, choose Environments, and then choose the name of your environment from the list.

3. 
    On the environment overview page, choose Upload and deploy.

4. 
    Use the on-screen dialog box to upload the source bundle.

5. 
    Choose Deploy.

6. 
    When the deployment completes, you can choose the site URL to open your website in a new tab.

Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk), and in the Regions list, select your AWS Region.
In the navigation pane, choose Environments, and then choose the name of your environment from the list.
On the environment overview page, choose Upload and deploy.
Use the on-screen dialog box to upload the source bundle.
Choose Deploy.
When the deployment completes, you can choose the site URL to open your website in a new tab.

###### Note
To optimize the source bundle further, initialize a Git repository and use the [git archive command](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/applications-sourcebundle.html#using-features.deployment.source.git) to create the source bundle. The default Laravel project includes a .gitignore file that tells Git to exclude the vendor folder and other files that are not required for deployment.

```
.gitignore
```


```
vendor
```

## Configure Composer settings
When the deployment completes, click the URL to open your Laravel application in the browser:
What's this? By default, Elastic Beanstalk serves the root of your project at the root path of the website. In this case, though, the default page (index.php) is one level down in the public folder. You can verify this by adding /public to the URL. For example, http://laravel.us-east-2.elasticbeanstalk.com/public.

```
index.php
```


```
public
```


```
/public
```


```
http://laravel.us-east-2.elasticbeanstalk.com/public
```


```
laravel
```


```
us-east-2
```

To serve the Laravel application at the root path, use the Elastic Beanstalk console to configure the document root for the website.

###### To configure your website's document root
1. Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk),
  and in the Regions list, select your AWS Region.
2. 
  In the navigation pane, choose Environments, and then choose the name of your environment from the list.

3. In the navigation pane, choose Configuration.
4. 
In the Updates, monitoring, and logging configuration category, choose Edit.
5. 
        For Document Root, enter /public.

6. 
To save the changes choose Apply at the bottom of the page.
7. 
        When the update is complete, click the URL to reopen your site in the browser.

Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk), and in the Regions list, select your AWS Region.
In the navigation pane, choose Environments, and then choose the name of your environment from the list.
In the navigation pane, choose Configuration.
In the Updates, monitoring, and logging configuration category, choose Edit.
For Document Root, enter /public.

```
/public
```

To save the changes choose Apply at the bottom of the page.
When the update is complete, click the URL to reopen your site in the browser.
So far, so good. Next you'll add a database to your environment and configure Laravel to connect to it.

Launch an RDS DB instance in your Elastic Beanstalk environment. You can use MySQL, SQLServer, or PostgreSQL databases with Laravel on Elastic Beanstalk. For this example, we'll use MySQL.

###### To add an RDS DB instance to your Elastic Beanstalk environment
1. Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk),
  and in the Regions list, select your AWS Region.
2. 
  In the navigation pane, choose Environments, and then choose the name of your environment from the list.

3. In the navigation pane, choose Configuration.
4. 
        In the Database configuration category, choose Edit.

5. 
        For Engine, choose mysql.

6. 
        Type a master username and password. Elastic Beanstalk will provide these values to your application using
          environment properties.

7. 
To save the changes choose Apply at the bottom of the page.
Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk), and in the Regions list, select your AWS Region.
In the navigation pane, choose Environments, and then choose the name of your environment from the list.
In the navigation pane, choose Configuration.
In the Database configuration category, choose Edit.
For Engine, choose mysql.
Type a master username and password. Elastic Beanstalk will provide these values to your application using environment properties.
To save the changes choose Apply at the bottom of the page.
Creating a database instance takes about 10 minutes. For more information about databases coupled to an Elastic Beanstalk environment, see [Adding a database to your Elastic Beanstalk environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.managing.db.html).
In the meantime, you can update your source code to read connection information from the environment. Elastic Beanstalk provides connection details using environment variables, such as RDS_HOSTNAME, that you can access from your application.

```
RDS_HOSTNAME
```

Laravel's database configuration is stored in a file named database.php in the config folder in your project code. Find the mysql entry and modify the host, database, username, and password variables to read the corresponding values from Elastic Beanstalk:

```
database.php
```


```
config
```


```
mysql
```


```
host
```


```
database
```


```
username
```


```
and password
```


###### Example~/Eb-laravel/config/database.php

```
... 'connections' => [ 'sqlite' => [ 'driver' => 'sqlite', 'database' => env('DB_DATABASE', database_path('database.sqlite')), 'prefix' => '', ], 'mysql' => [ 'driver' => 'mysql', 'host' => env('RDS_HOSTNAME', '127.0.0.1'), 'port' => env('RDS_PORT', '3306'), 'database' => env('RDS_DB_NAME', 'forge'), 'username' => env('RDS_USERNAME', 'forge'), 'password' => env('RDS_PASSWORD', ''), 'unix_socket' => env('DB_SOCKET', ''), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => null, ], ...
```


```
... 'connections' => [ 'sqlite' => [ 'driver' => 'sqlite', 'database' => env('DB_DATABASE', database_path('database.sqlite')), 'prefix' => '', ], 'mysql' => [ 'driver' => 'mysql', 'host' => env('RDS_HOSTNAME', '127.0.0.1'), 'port' => env('RDS_PORT', '3306'), 'database' => env('RDS_DB_NAME', 'forge'), 'username' => env('RDS_USERNAME', 'forge'), 'password' => env('RDS_PASSWORD', ''), 'unix_socket' => env('DB_SOCKET', ''), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => null, ], ...
```


```
RDS_HOSTNAME
```


```
RDS_PORT
```


```
RDS_DB_NAME
```


```
RDS_USERNAME
```


```
RDS_PASSWORD
```

To verify that the database connection is configured correctly, add code to index.php to connect to the database and add some code to the default response:

```
index.php
```


###### Example~/Eb-laravel/public/index.php

```
... if(DB::connection()->getDatabaseName()) { echo "Connected to database ".DB::connection()->getDatabaseName(); } $response->send(); ...
```


```
... if(DB::connection()->getDatabaseName()) { echo "Connected to database ".DB::connection()->getDatabaseName(); } $response->send(); ...
```


```
if(DB::connection()->getDatabaseName()) { echo "Connected to database ".DB::connection()->getDatabaseName(); }
```

When the DB instance has finished launching, bundle and deploy the updated application to your environment.

###### To update your Elastic Beanstalk environment
1. 
        Create a new source bundle:

## Add a database to your environment
~/eb-laravel$ zip ../laravel-v2-rds.zip -r * .[^.]* -x "vendor/*"

2. Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk),
  and in the Regions list, select your AWS Region.
3. 
  In the navigation pane, choose Environments, and then choose the name of your environment from the list.

4. 
        Choose Upload and Deploy.

5. 
        Choose Browse, and upload laravel-v2-rds.zip.

6. 
        Choose Deploy.

Create a new source bundle:

```
~/eb-laravel$ zip ../laravel-v2-rds.zip -r * .[^.]* -x "vendor/*"
```


```
~/eb-laravel$ zip ../laravel-v2-rds.zip -r * .[^.]* -x "vendor/*"
```


```
zip ../laravel-v2-rds.zip -r * .[^.]* -x "vendor/*"
```

Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk), and in the Regions list, select your AWS Region.
In the navigation pane, choose Environments, and then choose the name of your environment from the list.
Choose Upload and Deploy.
Choose Browse, and upload laravel-v2-rds.zip.

```
laravel-v2-rds.zip
```

Choose Deploy.
Deploying a new version of your application takes less than a minute. When the deployment is complete, refresh the web page again to verify that the database connection succeeded:

## Cleanup
After you finish working with the demo code, you can terminate your environment. Elastic Beanstalk deletes all related AWS resources, such as [Amazon EC2 instances](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.managing.ec2.html), [database instances](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.managing.db.html), [load balancers](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.managing.elb.html), security groups, and [alarms](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/using-features.alarms.html#using-features.alarms.title).
Removing resources does not delete the Elastic Beanstalk application, so you can create new environments for your application at any time.

###### To terminate your Elastic Beanstalk environment from the console
1. Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk),
  and in the Regions list, select your AWS Region.
2. 
  In the navigation pane, choose Environments, and then choose the name of your environment from the list.

3. 
    Choose Actions, and then choose Terminate
      environment.

4. 
    Use the on-screen dialog box to confirm environment termination.

Open the [Elastic Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk), and in the Regions list, select your AWS Region.
In the navigation pane, choose Environments, and then choose the name of your environment from the list.
Choose Actions, and then choose Terminate environment.
Use the on-screen dialog box to confirm environment termination.
In addition, you can terminate database resources that you created outside of your Elastic Beanstalk environment. When you terminate an Amazon RDS DB instance, you can take a snapshot and restore the data to another instance later.

###### To terminate your RDS DB instance
1. 
    Open the [Amazon RDS console](https://console.aws.amazon.com/rds).

2. 
    Choose Databases.

3. 
    Choose your DB instance.

4. 
    Choose Actions, and then choose Delete.

5. 
    Choose whether to create a snapshot, and then choose
      Delete.

Open the [Amazon RDS console](https://console.aws.amazon.com/rds).
Choose Databases.
Choose your DB instance.
Choose Actions, and then choose Delete.
Choose whether to create a snapshot, and then choose Delete.

For more information about Laravel, go to the Laravel official website at [laravel.com](https://laravel.com/).
As you continue to develop your application, you'll probably want a way to manage environments and deploy your application without manually creating a .zip file and uploading it to the Elastic Beanstalk console. The [Elastic Beanstalk Command Line Interface](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/eb-cli3.html) (EB CLI) provides easy-to-use commands for creating, configuring, and deploying applications to Elastic Beanstalk environments from the command line.
In this tutorial, you used the Elastic Beanstalk console to configure composer options. To make this configuration part of your application source, you can use a configuration file like the following.

###### Example.ebextensions/composer.config

```
option_settings: aws:elasticbeanstalk:container:php:phpini: document_root: /public
```


```
option_settings: aws:elasticbeanstalk:container:php:phpini: document_root: /public
```

For more information, see [Advanced environment customization with configuration files (.ebextensions)](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/ebextensions.html).
Running an Amazon RDS DB instance in your Elastic Beanstalk environment is great for development and testing, but it ties the lifecycle of your database to your environment. See [Adding an Amazon RDS DB instance to your PHP Elastic Beanstalk environment](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/create_deploy_PHP.rds.html) for instructions on connecting to a database running outside of your environment.
Finally, if you plan on using your application in a production environment, you will want to [configure a custom domain name](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/customdomains.html) for your environment and [enable HTTPS](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/configuring-https.html) for secure connections.
[Document Conventions](https://docs.aws.amazon.com/general/latest/gr/docconventions.html)
Thanks for letting us know we're doing a good job!
If you've got a moment, please tell us what we did right so we can do more of it.
Thanks for letting us know this page needs work. We're sorry we let you down.
If you've got a moment, please tell us how we can make the documentation better.

