# baoforce
========

Simple PHP Rest Salesforce abstraction API

This set of classes use a Log, Config, and Memcached abstraction classes.


### How to use ?

Below a little sample how to print a query result.

```php
<?php

$credential = new \baoforce\ConnectionCredentials(
	  "some@email.com",
	  "some-password",
	  "token",
	  "CLIENT_ID",    
	  "CLIENT_SECRET"  
);
$conn = new \baoforce\ConnectionRest($credential);

print_r( 
	  $conn->queryFirst("SELECT Id, Name FROM Account LIMIT 1")
);

```


Using a factory is able to have many credentials and one as default:


```php
<?php

\baoforce\ConnectionRestFactory::addCredentials(
	new \baoforce\ConnectionCredentials(
		  "some@email.com",
		  "some-password",
		  "token",
		  "CLIENT_ID",    
		  "CLIENT_SECRET"  
	)
);

\baoforce\ConnectionRestFactory::addCredentials(
	new \baoforce\ConnectionCredentials(
		  "some2@email.com",
		  "some2-password",
		  "token2",
		  "CLIENT_ID",    
		  "CLIENT_SECRET"  
	)
);

\baoforce\ConnectionRestFactory::setDefaultUsername("some@email.com");

$conn = \baoforce\ConnectionRestFactory::getInstance();

print_r( 
	  $conn->queryFirst("SELECT Id, Name FROM Account LIMIT 1")
);

```


