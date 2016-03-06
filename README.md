#AWS tools

CLI tools for AWS tasks

Currently consists of one command: aws-tools:ec2-cname

This is used to create CNAME records in AWS Route53 pointed to public DNS names for EC2 instances.

The command take a single parameter, which it then tries to match against a tag value set on an EC2 instance to locate the
machine to point the CNAME record at.  The subdomain part of the CNAME is also set by this value.

See src/Command/EC2CnameCommand/config.example.ini for more information.

An example may help...

Assume the following:
* You have the domain aws.example.com set up in route53 as a hosted zone.
* You have an EC2 instance with a tag called shortname whose value is test123
* you have amazons aws cli tool installed and have configured your AWS credentials 

You would then create a config.ini file based on the config.example.ini in which you would set:
```
zone = aws.example.com
subdomain_tag = shortname
ttl = 180
```

Then you could run
```
php run.php aws-tools:ec2-cname test123
```

... which would:

1. Find your first ec2 instance with a tag whose name is shortname and whose value is test123
2. Create the CNAME test123.aws.example.com and point it at the EC2 instance's public DNS name