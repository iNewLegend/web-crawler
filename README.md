![image](https://github.com/iNewLegend/web-crawler/assets/10234691/830714f3-c0ec-4b51-add7-d44b0598cc98)

# Requirements
- MongoDB
- PHP 
  - Min Version: `8.2`
  - MongoDB extension
  - Composer
- Node
    - NPM
    - Angular CLI

# Installation
- Clone the repository
    - $ `git clone https://github.com/iNewLegend/web-crawler.git`
    - $ `cd web-crawler`
    - $ `composer install`


- Setup mongodb connection
    - $ `cd crawler-backend`
    - $ `cp .env.example .env`
    - $ `vim .env`
        - Set `DB_DSN` to your mongodb connection string
        - Set `DB_DATABASE` to your mongodb database name
        - Save and exit
    - $ Create database collections
      - $ `php artisan migrate`
    - $ `cd ..`


- Run backend server
    - $ `composer dev-start-backend`
- Run frontend server (in another terminal)
    - $ `composer dev-start-frontend`

- Enter [http://localhost:4200/](http://localhost:4200/)

# Major commits
-  2023-08-31 08:00:00 ~ 14:30:00 `New: CrawlerLib - Initial Commit`
-  2023-08-31 14:30:00 ~ 16:00:00 `Internal: Workflows - Add test workflow`
-  2023-08-31 `Tweak: CrawlerLib - Add memory cache`
-  2023-09-01 `New: ClawlerBackend - POC`
-  2023-09-02 `New: CralwerFrontend - POC`
