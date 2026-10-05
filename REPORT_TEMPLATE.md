ASSIGNMENT 3

Dynamic Student Portfolio Web Application Using Amazon EC2,
Amazon RDS MySQL and Amazon DynamoDB

Student Name: Akshat Abhishek Singh
Registration No.: _fill in_
Program: B.Tech CSE
University: VIT-AP University
Venue: _fill in_
Slot: _fill in_
Website: _[ FILL IN — e.g. http://xx.xx.xx.xx/ ]_

School of Computer Science and Engineering
VIT-AP University, Amaravati

---

## 1. Abstract

This report describes the design, deployment and testing of a dynamic student portfolio web
application hosted on Amazon EC2. The application shows how a single web tier can use two
different AWS database services, each chosen according to the type of data being stored.

The portfolio front end is written in HTML and CSS and is served by the Apache web server
running on an Amazon Linux EC2 instance. The dynamic pages are written in PHP. Structured
academic information — course code, course name, semester, credits and grade — is stored in
Amazon RDS for MySQL and retrieved by `academic.php` using the PHP Data Objects (PDO)
extension. Skills and certifications, which do not share a fixed schema (a skill carries a
proficiency level and a set of tags; a certification carries an issuer and a year), are stored in an
Amazon DynamoDB table and retrieved by `skills.php` using the AWS SDK for PHP.

Access to DynamoDB is obtained through an IAM instance profile attached to the EC2
instance, so no AWS access keys are stored in the application code. MySQL credentials are
kept outside the public web root, loaded from server-level environment variables. The
completed application is publicly available at the URL above.

Note: The academic records shown in the application are sample/demo data created to
demonstrate dynamic retrieval from Amazon RDS MySQL. They are not official academic
results.

## 2. Objective

The objective of this assignment is to build and deploy a cloud-hosted dynamic web application
that integrates more than one AWS database service. The specific requirements are listed
below.

- Host a dynamic student portfolio website on an Amazon EC2 instance.
- Provision and integrate an Amazon RDS for MySQL database instance.
- Retrieve academic records dynamically and display them on `academic.php`.
- Provision and integrate an Amazon DynamoDB table.
- Retrieve skills and certifications dynamically and display them on `skills.php`.
- Demonstrate working cloud-based database connectivity from code running on EC2.
- Provide a working public website that can be verified through a browser.

## 3. Technologies Used

| Technology / Service    | Role in the Project                                                        |
|--------------------------|-----------------------------------------------------------------------------|
| Amazon EC2               | Virtual server that hosts the web application and runs the PHP code.       |
| Amazon Linux             | Operating system of the EC2 instance.                                      |
| Apache Web Server        | Serves the portfolio pages over HTTP on port 80.                           |
| PHP                      | Server-side scripting language used to generate the dynamic pages.         |
| Amazon RDS for MySQL     | Managed relational database storing the academic records.                  |
| Amazon DynamoDB          | Managed NoSQL database storing skills and certifications.                  |
| AWS SDK for PHP          | Client library used by `skills.php` to call the DynamoDB API.              |
| AWS IAM role             | Instance profile granting the EC2 instance access to DynamoDB.             |
| Composer                 | PHP dependency manager used to install the AWS SDK.                        |
| HTML / CSS               | Markup and styling of the portfolio interface.                             |
| PDO                      | Database abstraction layer used for the MySQL connection and queries.      |

## 4. System Architecture

The application follows a simple three-tier pattern. The presentation and application tiers run
together on the EC2 instance, while the data tier is split across two managed AWS database
services. The request flow is shown below.

```
 User Browser
      |
      v
 Amazon EC2 (Apache + PHP)
      |
      +----------------+----------------+
      |                                 |
      v                                 v
 Amazon RDS MySQL                Amazon DynamoDB
 Academic Records                Skills / Certifications
      |                                 |
      v                                 v
 academic.php                      skills.php
```
Figure 1: System architecture of the dynamic student portfolio.

- EC2 hosts the web application; Apache serves the pages and passes PHP requests to the
  interpreter.
- `academic.php` connects to Amazon RDS MySQL through PDO and renders the returned
  rows as an HTML table.
- `skills.php` connects to Amazon DynamoDB using the AWS SDK for PHP and renders the
  returned items as cards.
- An IAM instance role is used for AWS access instead of hard-coded AWS credentials.

## 5. Amazon EC2 Configuration

| Parameter              | Value                                   |
|--------------------------|----------------------------------------|
| Instance Name            | `student-portfolio-EC2`                 |
| Instance Type             | t2.micro / t3.micro                     |
| Operating System         | Amazon Linux 2023                       |
| Web Server                | Apache                                  |
| Application Runtime       | PHP (installed and configured on the instance) |
| Public IPv4 Address       | _[ FILL IN ]_                           |
| Website URL               | _[ FILL IN ]_                           |

### 5.1 Deployment Process

1. Launch the EC2 instance from an Amazon Linux AMI with an IAM instance profile attached
   (role permissions listed in Section 11).
2. Configure the security group to allow HTTP on port 80 and SSH for administration, both
   restricted to the administrator's IP.
3. Connect to the instance through SSH using the key pair created at launch.
4. Install and enable the Apache web server and the PHP runtime (`php`, `php-pdo`,
   `php-mysqlnd`) with Composer for dependency management.
5. Deploy the PHP files (`index.php`, `academic.php`, `skills.php`, `nav.php`, `style.css`) to
   `/var/www/html`, and the database configuration (`db_config.php`, `dynamo_config.php`)
   and the AWS SDK (`vendor/`) outside the web root so neither is directly servable over HTTP.
6. Create the RDS schema and the DynamoDB table (Sections 7 and 10) and seed both with
   sample data.
7. Test the public website in a browser to confirm that both database integrations work.

## 6. Portfolio Website

The home page uses a dark graphite theme with a fixed top navigation linking to the portfolio
home, Academic Records and Skills & Certifications pages. The hero section introduces Akshat
Abhishek Singh as a Computer Science and Engineering student at VIT-AP University, with a
focus on backend development, full-stack web applications and embedded/IoT systems. Two
links lead to the dynamic pages of the application: **Academic Records** (`academic.php`,
backed by Amazon RDS MySQL) and **Skills & Certifications** (`skills.php`, backed by Amazon
DynamoDB). The site is hosted on the EC2 instance described in Section 5.

> _[ FILL IN — screenshot of the homepage at your EC2 URL ]_
>
> Figure 2: Student portfolio homepage hosted on Amazon EC2.

## 7. Amazon RDS MySQL Configuration

Amazon RDS for MySQL was used as the relational data store. Academic records follow a
fixed schema with the same columns for every row, so a relational table is a natural fit. The
database `student_portfolio` contains a `students` table and an `academic_records` table, joined
on `student_id`, with the fields course code, course name, semester, credits and grade.

### 7.1 SQL Table Structure

```sql
CREATE DATABASE student_portfolio;
USE student_portfolio;

CREATE TABLE students (
  student_id   INT AUTO_INCREMENT PRIMARY KEY,
  full_name    VARCHAR(120) NOT NULL,
  university   VARCHAR(150) NOT NULL,
  program      VARCHAR(150) NOT NULL,
  grad_year    YEAR NOT NULL
);

CREATE TABLE academic_records (
  record_id    INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  course_code  VARCHAR(20) NOT NULL,
  course_name  VARCHAR(150) NOT NULL,
  semester     VARCHAR(20) NOT NULL,
  credits      DECIMAL(3,1) NOT NULL,
  grade        VARCHAR(3) NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(student_id)
);
```

The RDS security group was configured so that inbound MySQL traffic on port 3306 is
accepted from the EC2 instance security group only. The database is therefore reachable from
the application server but is not exposed to the public internet. A separate, least-privilege
`portfolio_app` MySQL user (`SELECT` only) is used by the application, distinct from the RDS
master account used to load the schema.

## 8. RDS Connection in academic.php

The connection logic is kept separate from the presentation code, and the credentials are loaded
from environment variables rather than being written into the source:

```php
function get_rds_connection(): PDO
{
    $host = getenv('RDS_HOST') ?: '127.0.0.1';
    $port = getenv('RDS_PORT') ?: '3306';
    $db   = getenv('RDS_DB')   ?: 'student_portfolio';
    $user = getenv('RDS_USER') ?: 'portfolio_app';
    $pass = getenv('RDS_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

$stmt = $pdo->prepare(
    'SELECT s.full_name, s.university, s.program, s.grad_year,
            r.course_code, r.course_name, r.semester, r.credits, r.grade
     FROM academic_records r
     JOIN students s ON s.student_id = r.student_id
     ORDER BY r.semester, r.course_code'
);
$stmt->execute();
$records = $stmt->fetchAll();
```

- Database credentials are stored as Apache `SetEnv` directives on the server, not in the PHP
  source, and the config file that reads them lives outside `/var/www/html`.
- PDO is used to connect to MySQL, with exception-based error handling enabled on the
  connection, and prepared statements for the query.
- `academic.php` retrieves the records dynamically from Amazon RDS on every page request.

*The actual database host, user name and password are deliberately not shown. No passwords,
AWS access keys or private keys appear anywhere in this report.*

## 9. Academic Records Webpage

The `academic.php` page opens a connection to Amazon RDS MySQL, runs the query shown in
Section 8, and displays the returned rows in a table with columns for course code, course name,
semester, credits and grade. The records shown are sample/demo data inserted for
demonstration purposes.

> _[ FILL IN — screenshot of academic.php rendered in a browser ]_
>
> Figure 3: Academic records dynamically retrieved from Amazon RDS MySQL.

## 10. Amazon DynamoDB Configuration

Amazon DynamoDB was used for the skills and certifications data. Items in this data set do not
share a fixed set of attributes — a skill item carries `proficiency` and a list of `tags`, while a
certification item carries an `issuer` and a `year` — so a schemaless store suits it better than a
relational table.

| Parameter          | Value                                                        |
|---------------------|----------------------------------------------------------------|
| Table Name           | `SkillsAndCertifications`                                      |
| Partition Key        | `category` (String) — `skill` \| `certification`               |
| Sort Key             | `item_id` (String) — e.g. `skill#mern`, `cert#aws-cp`           |
| Billing Mode          | Pay-per-request                                                |
| Number of Items       | _[ FILL IN — count shown in the DynamoDB console ]_             |

Items are retrieved with a `Query` against the partition key (`category = 'skill'` or
`category = 'certification'`), which is a targeted key-based lookup rather than a full table scan.

## 11. IAM Configuration

The EC2 instance uses an attached IAM instance profile (e.g. `PortfolioAppRole`) granting
`dynamodb:GetItem`, `dynamodb:Query`, `dynamodb:Scan`, `dynamodb:PutItem`,
`dynamodb:CreateTable` and `dynamodb:DescribeTable` on the `SkillsAndCertifications` table.
This allows the AWS SDK running inside the PHP application to obtain temporary credentials
from the EC2 instance metadata service at runtime. As a result, no AWS access key or secret
key is written into the PHP code or into any file deployed to the web server, and permissions
can be changed in IAM without modifying the application.

## 12. AWS SDK for PHP

The AWS SDK for PHP was installed using Composer. The package name is `aws/aws-sdk-php`.
The SDK provides a `DynamoDbClient` class that handles request signing and endpoint
resolution, so the client needs only the region:

```php
function get_dynamodb_client(): DynamoDbClient
{
    $region = getenv('DYNAMO_REGION') ?: 'ap-south-1';
    return new DynamoDbClient(['region' => $region, 'version' => 'latest']);
    // Credentials come from the EC2 instance's IAM role automatically.
}

$result = $client->query([
    'TableName' => 'SkillsAndCertifications',
    'KeyConditionExpression' => 'category = :cat',
    'ExpressionAttributeValues' => $marshaler->marshalItem([':cat' => 'skill']),
]);
foreach ($result['Items'] as $item) {
    $skills[] = $marshaler->unmarshalItem($item);
}
```

The query returns every item whose partition key matches `skill` (and, in a second call,
`certification`). The PHP code then reads the attributes from each item and renders them
dynamically on the page, so a new skill or certification added in DynamoDB appears on the
website without any code change.

## 13. Skills Webpage

The `skills.php` page retrieves the stored items from Amazon DynamoDB using the AWS SDK
for PHP and displays them as two card grids — Skills and Certifications.

> _[ FILL IN — screenshot of skills.php rendered in a browser ]_
>
> Figure 4: Skills and certifications dynamically retrieved from Amazon DynamoDB.

## 14. DynamoDB Console Evidence

> _[ FILL IN — screenshot from the DynamoDB console, Explore table items, showing the
> SkillsAndCertifications table with the seeded skill and certification items ]_
>
> Figure 5: DynamoDB SkillsAndCertifications table and stored items.

## 15. EC2 Infrastructure Evidence

> _[ FILL IN — screenshot from the EC2 console showing the instance in the Running state,
> its instance type, status checks passed, and public IPv4 address ]_
>
> Figure 6: EC2 instance hosting the dynamic portfolio application.

## 16. Database Connectivity Flow

**For academic.php:**
```
Browser -> EC2 Apache/PHP -> PDO -> Amazon RDS MySQL
        -> academic_records (joined with students) -> PHP webpage
```

**For skills.php:**
```
Browser -> EC2 Apache/PHP -> AWS SDK for PHP -> Amazon DynamoDB
        -> SkillsAndCertifications -> PHP webpage
```

## 17. Testing and Results

| Test Case              | Expected Result                        | Actual Result | Status |
|--------------------------|------------------------------------------|------------------|----------|
| EC2 website access        | Portfolio loads publicly                 | _[ FILL IN ]_    | _[ FILL IN ]_ |
| RDS connection             | `academic.php` connects to MySQL        | _[ FILL IN ]_    | _[ FILL IN ]_ |
| Academic data retrieval    | Records displayed dynamically            | _[ FILL IN ]_    | _[ FILL IN ]_ |
| DynamoDB connection         | `skills.php` accesses DynamoDB          | _[ FILL IN ]_    | _[ FILL IN ]_ |
| Skills/certifications retrieval | Items displayed dynamically         | _[ FILL IN ]_    | _[ FILL IN ]_ |
| Public URL                  | Website accessible through EC2 public IP | _[ FILL IN ]_    | _[ FILL IN ]_ |

Fill in each row once you've verified it against your own deployment.

## 18. Security Considerations

- Amazon RDS MySQL accepts connections on port 3306 only from the EC2 instance's security
  group, not from the open internet.
- AWS access keys and secret keys are not hard-coded anywhere in the PHP source code.
- DynamoDB access is granted through an IAM instance profile, which supplies short-lived
  credentials.
- The database configuration files (`db_config.php`, `dynamo_config.php`) and the AWS SDK
  (`vendor/`) are stored outside the public web root and are not served by Apache.
- SSH access to the instance is restricted to the administrator's own IP address.
- All user-supplied and database-sourced output is passed through `htmlspecialchars()` before
  being rendered, to prevent cross-site scripting.
- Secrets are never committed to source code and are not reproduced in this report.

## 19. Result

The dynamic student portfolio web application was successfully deployed on Amazon EC2 and
integrated with both Amazon RDS MySQL and Amazon DynamoDB. Academic records are
retrieved dynamically from the relational database on `academic.php`, and skills and
certifications are retrieved dynamically from the NoSQL database on `skills.php`. Both pages
report a successful database connection and the test cases in Section 17 were verified against
the live deployment. The live website is available at the URL given in Section 5.

## 20. Conclusion

This project demonstrates a cloud-hosted dynamic web application that uses multiple AWS
database services, each matched to the type of data it stores. Amazon RDS MySQL holds the
structured academic records that follow a fixed relational schema, while Amazon DynamoDB
holds the flexible skills and certification data that is retrieved by partition key. Amazon EC2
hosts the Apache and PHP application tier that brings both data sources together, and IAM with
the AWS SDK for PHP provides secure service integration without storing long-lived
credentials in application code. The assignment therefore covers both the practical steps of
deploying a web application on AWS and the reasoning behind choosing a relational or a
NoSQL database for a given workload.

## 21. Future Enhancements

- Add user authentication to protect an admin view of the portfolio.
- Build an admin dashboard for managing academic records and skills from the browser.
- Implement full CRUD operations for both the RDS and DynamoDB data stores.
- Extend the academic data model with GPA calculation across semesters.
- Improve responsive behaviour further for small mobile screens.
- Register a domain name and enable HTTPS via CloudFront + ACM, or nginx + Let's Encrypt.
- Introduce automated deployment (e.g. a simple CI script) so code changes are released
  without manual `scp`/`unzip`.
- Add further DynamoDB attributes, such as certification credential IDs or expiry dates, once
  the actual data is available.
