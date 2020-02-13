# Djamp - Framework MVC
[![Build Status](https://travis-ci.com/dandico23/DjampMVC.svg?branch=master)](https://travis-ci.com/dandico23/DjampMVC) [![Maintainability](https://api.codeclimate.com/v1/badges/cc1c399ebf5c4da86fb2/maintainability)](https://codeclimate.com/github/dandico23/djamp-slim-mvc/maintainability)
<a href="https://packagist.org/packages/djamp/djamp"><img src="https://poser.pugx.org/djamp/djamp/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/djamp/djamp"><img src="https://poser.pugx.org/djamp/djamp/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/djamp/djamp"><img src="https://poser.pugx.org/djamp/djamp/license.svg" alt="License"></a>

Framework MVC desenhado para aplicações que tenham multiplas fontes de dados(databases, APis) e multiplos ambientes(produção, desenvolvimento, homologação, treinamento). utiliza Twig como engine de templates, Flash para mensagens entre requisições.

### Requisitos
- PHP 7.3.12
- PHP-CLI
- pdo_pgsql(extensão php)
- pdo-oci(extensão php)
- Composer

### Bancos de dados compativeis
- Oracle
- Postgres
- Mysql

### Instalação Rápida

```
composer create-project djamp/djamp
```
### Criando classes automaticamente

Se o PHP-CLI estiver instalado é possivel usar a linha de comando para criar novas classes para o seu projeto

#### Criando uma class Controller
```
php djamp create:Controller ExemploController
```

#### Criando uma class Model
```
php djamp create:Model ExemploModel
```

### Mapeando os ambientes

O Djamp mapeia as fontes de dados conforme parametros definidos na URI.
Exemplo:

O domino principal da sua aplicação é:
exemplo.com.br

Você definiu que os ambientes da aplicação serão os seguintes:

exemplo.com.br - ambiente de produção
exemplo.com.br/desenvolvimento- - ambiente de desenvolvimento
exemplo.com.br/homologacao - ambiente de homologação
exemplo.com.br/treinamento - ambiente de treinamento

esse seria o seu arquivo Config/state.ini

```
homolog = homologacao
develop = desenvolvimento
training = treinamento
```
