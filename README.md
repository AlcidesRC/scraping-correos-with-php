# Scraping Correos with PHP

[TOC]

> [!TIP]
>
> This `Markdown` document may contains some [Mermaid](https://mermaid.js.org/) diagrams so please consider install [Typora](https://typora.io/) to read/manage `Markdown` files and don't miss any advanced feature. 



## Summary

This repository contains a web scraper that allows you to build the Spanish postal codes database from [Sociedad Estatal de Correos y Telégrafos](https://www.correos.es/).

The application is built on top of **PHP + Guzzle + concurrent requests** to improve the performance.



------



## Technical Requirements

| Tool   | Required/Recommended | Description                                  |
| ------ | -------------------- | -------------------------------------------- |
| Git    | Required             | To interact with the VCS repository          |
| Docker | Required             | To manage the development environment        |
| Make   | Recommended          | To interact with the development environment |

### Available Commands

```bash
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                           .: AVAILABLE COMMANDS :.                           ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

· show-context                   Setup: show context
· build                          Docker: builds the service
· up                             Docker: starts the service
· restart                        Docker: restarts the service
· down                           Docker: stops the service
· logs                           Docker: exposes the service logs
· bash                           Docker: establish a bash session into main container
· init                           Application: initializes the scrape process
```



------



## Analysis

Postal codes in Spain were created on July 1, 1984, when the Sociedad Estatal de Correos y Telégrafos (https://www.correos.es/) introduced automated mail sorting. 

A postal code **consists of a five-digit number between 01000..52999**, where the first two digits (01..52) correspond to one of the 50 provinces of Spain or to one of the two Spanish autonomous cities on the African coast. The last three digits correspond to the postal codes available in each province.

> You can find the list of provinces and their corresponding codes at the [Instituto Nacional de Estadística](https://www.ine.es/en/daco/daco42/codmun/cod_provincia_en.htm).

For example:

| Province  | Province ID | Range  | Possible Postcodes |
| --------- | ----------- | ------ | ------------------ |
| Barcelona | 08          | 0..999 | 08000..08999       |
| Málaga    | 29          | 0..999 | 29000..29999       |
| Ceuta     | 51          | 0..999 | 51000..51999       |

Postal codes in Spain were created on July 1, 1984 when the [Sociedad Estatal de Correos y Telégrafos](https://www.correos.es/), also known as Correos, introduced automated mail sorting. This company provides [a web form from which postal codes can be found](https://www.correos.es/es/en/tools/codigos-postales/details). If we open the *Developer Tools / Network* tab we will see that a background request is made to retrieve postal code suggestions.



> [!IMPORTANT]
>
> This application uses that *endpoint* to check if a postal code exists. 



### Postal Codes

A postal code **consists of a five-digit number between 01000..52999**, where the first two digits (01..52) correspond to one of the 50 provinces of Spain or to one of the two Spanish autonomous cities on the African coast. The last three digits correspond to the postal codes available in each province.

#### Examples

| Province  | Province ID | Range  | Possible Postcodes |
| --------- | ----------- | ------ | ------------------ |
| Barcelona | 08          | 0..999 | 08000..08999       |
| Málaga    | 29          | 0..999 | 29000..29999       |
| Ceuta     | 51          | 0..999 | 51000..51999       |



> [!TIP]
>
> You can find the list of Spanish provinces and their corresponding codes at the [Instituto Nacional de Estadística](https://www.ine.es/en/daco/daco42/codmun/cod_provincia_en.htm).



### Endpoint

#### Valid Requests

```text
GET https://api1.correos.es/digital-services/searchengines/api/v1/suggestions?text=08001
```

##### Response

HTTP 200 OK

```json
{
  "suggestions": [
    {
      "text": "08001, Barcelona, Barcelona, Cataluña, ESP",
      "longitude": 2.1686990270000592,
      "latitude": 41.380160001000036
    }
  ]
}
```

#### Unvalid Requests

```text
https://api1.correos.es/digital-services/searchengines/api/v1/suggestions?text=52999
```

##### Response

HTTP 200 OK

```json
{
  "code": "404",
  "message": "Not Found",
  "moreInformation": {
    "description": "Not results found.",
    "link": "www.correos.es"
  }
}
```



------



## Implementation

For each province this application generates all possible postal code combinations and checks their existence using the endpoint described above.



> [!IMPORTANT]
>
> If the answer is valid then it stores the postal code details into a CSV file for easy processing.



> [!IMPORTANT]
>
> The scrape process is performed by a PHPUnit unit tests because using tests allows long executions without any timeout and additionally it allows to validate the imported data in each iteration. 



------



## Getting Started

Just clone the repository into your preferred path:

```bash
$ mkdir -p ~/path/to/my-new-project && cd ~/path/to/my-new-project
$ git clone git@github.com:alcidesrc/scraping-correos-with-php.git .
```

### Start the scrape process

```bash
$ make init

 ℹ  Stopping the service 

[+] Running 2/2
 ✔ Container correos-app-run-7e80add999ab  Removed           10.2s 
 ✔ Network correos_default                 Removed           0.2s 

 ✓  Task done!


 ℹ  Building the image 

[+] Building 1.1s (24/24) FINISHED                           docker:default
 => [app internal] load build definition from Dockerfile     0.0s
 => => transferring dockerfile: 4.76kB                       0.0s
 => [app internal] load .dockerignore                                                                                               ...
 => => naming to docker.io/library/correos:dev               0.0s

 ✓  Task done!


 ℹ  Installing PHP dependecies... 

docker compose run --rm --user 1000:1000 app composer install
[+] Creating 1/1
 ✔ Network correos_default  Created                          0.1s 
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Nothing to install, update or remove
Generating optimized autoload files
32 packages you are using are looking for funding.
Use the `composer fund` command to find out more!

 ✓  Task done!


 ℹ  Generating CSV files... 
 
PHPUnit 11.3.1 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.10 with PCOV 1.0.11
Configuration: /code/phpunit.xml
Random Seed:   2453001523

.......................................................           55 / 55 (100%)

Time: 18:15.756, Memory: 14.00 MB

Correos (Tests\Unit\Importers\Correos)
 ✔ Check exception is raised with wrong province
 ✔ Scrape province with ALBACETE
 ✔ Scrape province with ÁVILA
 ✔ Scrape province with CÓRDOBA
 ✔ Scrape province with ZARAGOZA
 ✔ Scrape province with MELILLA
 ✔ Scrape province with HUESCA
 ✔ Scrape province with RIOJA,·LA
 ✔ Scrape province with BIZKAIA
 ✔ Scrape province with CORUÑA,·A
 ✔ Scrape province with BURGOS
 ✔ Scrape province with TOLEDO
 ✔ Scrape province with CASTELLÓN/CASTELLÓ
 ✔ Scrape province with MADRID
 ✔ Scrape province with LLEIDA
 ✔ Scrape province with BADAJOZ
 ✔ Scrape province with ARABA/ÁLAVA
 ✔ Scrape province with GUADALAJARA
 ✔ Scrape province with CEUTA
 ✔ Scrape province with BARCELONA
 ✔ Scrape province with BALEARS,·ILLES
 ✔ Scrape province with VALLADOLID
 ✔ Scrape province with PALMAS,·LAS
 ✔ Scrape province with TERUEL
 ✔ Scrape province with ALICANTE/ALACANT
 ✔ Scrape province with SEVILLA
 ✔ Scrape province with CUENCA
 ✔ Scrape province with SALAMANCA
 ✔ Scrape province with GRANADA
 ✔ Scrape province with NAVARRA
 ✔ Scrape province with GIRONA
 ✔ Scrape province with SEGOVIA
 ✔ Scrape province with ALMERÍA
 ✔ Scrape province with SORIA
 ✔ Scrape province with MÁLAGA
 ✔ Scrape province with ZAMORA
 ✔ Scrape province with SANTA·CRUZ·DE·TENERIFE
 ✔ Scrape province with GIPUZKOA
 ✔ Scrape province with TARRAGONA
 ✔ Scrape province with LEÓN
 ✔ Scrape province with CÁDIZ
 ✔ Scrape province with HUELVA
 ✔ Scrape province with JAÉN
 ✔ Scrape province with CANTABRIA
 ✔ Scrape province with ASTURIAS
 ✔ Scrape province with CÁCERES
 ✔ Scrape province with CIUDAD·REAL
 ✔ Scrape province with PONTEVEDRA
 ✔ Scrape province with LUGO
 ✔ Scrape province with VALENCIA/VALÈNCIA
 ✔ Scrape province with PALENCIA
 ✔ Scrape province with MURCIA
 ✔ Scrape province with OURENSE
 ✔ Validate first postal code from specific provinces with ARABA/ÁLAVA
 ✔ Validate first postal code from specific provinces with ALBACETE

OK (55 tests, 110 assertions)

Generating code coverage report in HTML format ... done [00:00.015]


Code Coverage Report:    
  2024-08-20 12:36:15    
                         
 Summary:                
  Classes: 50.00% (1/2)  
  Methods: 83.33% (5/6)  
  Lines:   97.18% (69/71)

App\CsvHandler
  Methods:  50.00% ( 1/ 2)   Lines:  88.89% ( 16/ 18)
App\Importers\Correos
  Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 53/ 53)

 ✓  Task done!
```



> [!TIP]
>
> CSV files are stored at `./src/output/province-XX.csv` for easy processing

 

> [!IMPORTANT]
>
> Here you can find [a Gist with all Spanish postal codes](https://gist.github.com/AlcidesRC/14f80f7842acc91e14c11dc22b52d177) combined into one single file.



------




## Security Vulnerabilities

Please review our security policy on how to report security vulnerabilities:

**PLEASE DON'T DISCLOSE SECURITY-RELATED ISSUES PUBLICLY**

### Supported Versions

Only the latest major version receives security fixes.

### Reporting a Vulnerability

If you discover a security vulnerability within this project, please [open an issue here](./issues). All security vulnerabilities will be promptly addressed.



------



## License

The MIT License (MIT). Please see [LICENSE](./LICENSE) file for more information.