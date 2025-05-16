# PentavalueTask
## Summary
This Project Structured Using SOLID & MVC Pattern
``Micro Frame Work`` Inpired From Art Of Laravel Structure build by Native Code using PSR-4 Standard Some Help From AI To Gen Fronted Design Structure Build By Me :)
![](frame.gif)


## Tree Project
```
│   .env.example
│   .gitignore
│   Backend Task.pdf
│   composer.json
│   destroy.bat
│   docker-compose.yml
│   index.php
│   Installation.bat
│   LICENSE
│   README.md
│   RealTime Orders Revenue.postman_collection.json
│   Redis.php
│   server.php
│   start-all.bat
│
├───App
│       SalesController.php
│
├───Connectivity
│       DB.php
│       DBConnectionInterface.php
│       MySQLConnection.php
│
├───Events
│       getAnalyticsData.php
│
├───MindMap
│       Router.php
│
└───public
        .htaccess
        index.html
        test-api.html
```



## Installation
#### Automatic
Run `Installation.bat` to install composer and copy `.env.example` => `.env`  then Run `start-all.bat` to run Server & Socket
- [http://127.0.0.1:8000](http://127.0.0.1:8000/)

#### Manual
- `cp .env.example .env`
- `composer install`
- `php -S 127.0.0.1:8000`
- `php server.php` | `php Redis.php`
- run server on [http://127.0.0.1:8000](http://127.0.0.1:8000/)
## Environment Variables

To run this project, you will need to add the following environment variables to your .env file

Connection:

`HOST`

`DB_NAME`

`USER_NAME`

`PASSWORD`

API INTEGRATIONS:

`OPENAI_API_KEY`

`WATHER_API_KEY`

`CITY`


## Authors

[Website](https://www.coder79.me/)
| [Linkedin](https://www.linkedin.com/in/devahmedsaeed/)
| [Youtube](https://www.youtube.com/AhmedSaeedcoder79/)

