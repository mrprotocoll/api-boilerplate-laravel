<div align="center">
  <h2><b>🕹️🕹️ Laravel API Boilerplate🕹️🕹️</b></h2>
  <br/>
</div>

<a name="readme-top"></a>

<!-- TABLE OF CONTENTS -->

# 📗 Table of Contents

- [📖 About the Project](#about-project)
    - [🛠 Built With](#built-with)
        - [Tech Stack](#tech-stack)
    - [🚀 Links](#api-docs)
    - [Features](#features)
- [💻 Getting Started](#getting-started)
    - [Setup](#setup)
    - [Prerequisites](#prerequisites)
    - [Usage](#usage)
- [🤝 Contributing](#contributing)

<!-- PROJECT DESCRIPTION -->

# 📖  API Boilerplate <a name="about-project"></a>

A robust, modular Laravel 11 API boilerplate built using a Domain-Driven Design (DDD) approach. This boilerplate provides a scalable foundation for modern API development with essential features like role-based access, versioning, Google OAuth integration, and more..

### Features

- Domain-Driven Architecture: Organized modules and shared resources for scalability. 
- Versioning: Supports multiple API versions with structured routing. 
- Role-Based Access Control: Integrated roles and permissions with easy extensibility. 
- Google OAuth: Simplified OAuth 2.0 authentication setup. 
- Modular Components: Independent modules for Auth, User, and more. 
- Enums and Helpers: Centralized, reusable enums and helper methods. 
- Custom Middlewares: Pre-configured middlewares for JSON responses, caching, logging, and preventing duplicate requests. 
- Optimized Exception Handling: Friendly and consistent error responses. 
- Custom Base Model: A UUID-enabled base model for consistency across entities. 
- Seeders and Factories: Simplified data seeding with predefined roles. 
- Rate Limiting: API throttling to prevent abuse. 
- Fully Documented: Swagger/OpenAPI support for API documentation.

### Architecture Overview
#### Domain-Driven Design (DDD)
The project structure is organized to separate concerns:

`src/modules`: Contains feature-specific modules, e.g., Auth and User.
`src/shared`: Shared resources like helpers, enums, and base classes.

#### Versioning
Version-specific modules and routes are located in the `V1` directory for flexibility.

### Tech Stack <a name="tech-stack"></a>

- <a href="https://www.php.net/">PHP</a>
- <a href="https://laravel.com/">Laravel</a>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- Link to Api Documentation -->

## 🚀 Links <a name="api-docs"></a>

To access the documentation goto the below link

- Link to api routes
```
http://localhost:8000/v1
```
- Link to documentation
```
http://localhost:8000/v1/documentation
```

<br/>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- GETTING STARTED -->

## 💻 Getting Started <a name="getting-started"></a>

To get a local copy up and running, follow these steps.

### Prerequisites

In order to run this boilerplate, you need:

1. PHP ^8.2 <br>
   use the following link to setup `PHP` if you dont have it already installed on your computer
<p align="left">(<a href="https://www.php.net/manual/en/install.php">install PHP</a>)</p>

2. Composer <br>
   use the following link to Download `Composer` if you dont have it already installed on your computer
<p align="left">(<a href="https://getcomposer.org/download/">install Composer</a>)</p>

## Install
clone the repository:
```
git clone git@github.com:mrprotocoll/api-boilerplate-laravel.git
```

Install dependencies:

```
composer install
```

## Setup

Create your database.

create .env file, change using the .env.example file and update the Database, Google Oauth (optional), and Email credentials.
```
cp .env.example .env
```

Generate keys, Run the migration and seed roles:

```
php artisan key:generate 
php artisan migrate --seed
```

### Usage

The following command can be used to run the application.

```sh
  php artisan serve
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Contributing
Feel free to fork the repository, make changes, and submit pull requests. Feedback is always welcome!

## License
This project is licensed under the MIT License.
