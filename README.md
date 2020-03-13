# Djamp - Framework MVC
[![Build Status](https://travis-ci.com/dandico23/DjampMVC.svg?branch=master)](https://travis-ci.com/dandico23/DjampMVC) 
[![Maintainability](https://api.codeclimate.com/v1/badges/fa8099df38d5d3f4b5f1/maintainability)](https://codeclimate.com/github/dandico23/DjampMVC/maintainability)
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


Esse seria o seu arquivo Config/state.ini:

```
homolog = homologacao
develop = desenvolvimento
training = treinamento
```

### Declarar uma Model

```
$examplesModel = $this->loadModel('Examples');
```

O nome do arquivo de Model deve ser, para esse caso, ExamplesModel.

### Estabelecimento de conexão com banco de dados

Dentro de algum arquivo de model:

```
$this->initDatabase("banco_mysql");
```

O nome_da_database deve ser declarado no arquivo database.ini, antecedido pelo ambiente.
Por exemplo, para declarar um banco chamado banco_mysql que é usado em ambiente de produção,
a declaração deve ser feita da seguinte forma:

```
[default_banco_mysql]
  host=127.0.0.1
  port=3308
  type=mysql
  dbname=test_db
  user=root
  password=
  scheme=
```

### Chamada de funções do banco de dados

A classe MyPDO implementa as funções de CRUD e paginação. Todos os models criados herdam essa classe 
e podem realizar as queries de forma simplificada. A seguir são demonstrados exemplos para a chamada
de cada operação. 

#### Insert

Recebe como argumento o nome da tabela e os valores das colunas a serem inseridos em forma de array:

```
$values = array("column1" => "value1", "column2" => "value2", ...);
$inserted_element = $this->container["database_name"]->insert('table_name', $values);
```

#### Select

Recebe como argumento a query sql a ser executada:

```
$sql = "SELECT * FROM table_name WHERE column1 = 'value1'";
$result_elements = $this->container["database_name"]->select($sql);
```

#### Update

Recebe como argumento os valores a serem atualizados em formato de array. Como argumento opcional,
podem ser passados filtros para os elementos a serem atualizados, caso contrário, todos os elementos
sofrem alteração:

```
# Update Sem filtros
$values = array("column1" => "updated");
$number_of_updated = $this->container["database_name"]->update('table_name', $values);

# Update Com filtros
$values = array("column1" => "updated");
$where = array("Column1" => 'filtro');
$number_of_updated = $this->container["database_name"]->update('table_name', $values, $where);
```

#### Delete

Recebe como argumento a query sql a ser executada:

```
$sql = "DELETE * FROM table_name WHERE column1 = 'value1'";
$number_of_deleted = $this->container["database_name"]->delete($sql);
```

#### Paginate

Existem duas funções responsáveis por realizar a paginação. A primeira delas recebe como argumento
o nome da tabela e o número de elementos por página e tem como retorno as seguintes variáveis:

- total_pages: número total de páginas
- first_page: lista com os elementos da primeira página
- cipher_text: identificador usado para requisitar demais páginas
- iv: identificador usado para requisitar demais páginas

As variáveis cipher_text e iv são argumentos para a função que retorna as páginas seguintes da tabela.

```
# Get first page
$elements_per_page = 5;
$result_array = $this->container["database_name"]->paginateGetTotalPages('table_name', $elements_per_page);
$first_page = $result_array['first_page'];
$total_pages = $result_array['total_pages'];
$cipher_text = $result_array['cipher_text'];
$iv = $result_array['iv'];

# Get second page
$page = 2;
$second_page = $this->container["database_name"]->selectPaginate($cipher_text, $iv, $page);
```

### Validação

A biblioteca Validator implementa diversas validações que podem ser acessadas da seguinte forma nos models e controllers:

```
$valid_result = $this->validator->validate($data, $rules);
```

As variáveis $data e $rules devem ser arrays contendo, respectivamente, os campos a serem validados e as regras de validação.
Múltiplas regras podem ser passados, se separados por '|'.
Exemplo:

```
$data = [
    'email' => 'daniel@mail.com',
    'age' => 2,
    'expire_date' => 'tomorrow',
    'punctuation' => 10,
    'color' => 'blue',
    'phone' => '34358525'
];
$rules = [
    'email' => 'required|email',
    'age' => 'required|numeric',
    'expire_date' => 'date|after:now',
    'punctuation' => 'numeric|between:1,11|different:4,5',
    'color' => 'string|in:red,green,blue',
    'phone' => 'size:8'
];
```

As seguintes validações estão disponíveis:

- required - field must exist in $data
- email - field must be in mail format
- numeric - field must be a number
- accepted - The field under validation must be yes, on, 1, or true
- after:date - The field under validation must be a value after a given date.
          The dates will be passed into the strtotime PHP function
- after_or_equal:date - Similar to after, but considering equal
- before:date - Similar to previous 2 arguments
- alpha - The field under validation must be entirely alphabetic characters.
- alpha_numeric - The field under validation may have alpha-numeric characters
- between:min,max - The field under validation must have a size between the given min
          and max (equal not included). Strings and arrays are evaluated based in size
- boolean - The field under validation must be able to be cast as a boolean.
            Accepted input are true, false, 1, 0, "1", and "0".
- date - The field under validation must be a valid date according to
         the strtotime PHP function
- date_equals:date - The field under validation must be equal to the given date
                    The dates will be passed into the PHP strtotime function.
- date_format:format - The field under validation must match the given format,
                       function \DateTime::createFromFormat is used
- different:field1,field2 - The field under validation must have a different
                            value than given fields.
- digits:value - The field under validation must be numeric and must have an exact length of value.
- digits_between:min,max - The field under validation must have a length between
                    the given min and max (equal not included).
- in:foo,bar - The field under validation must be included in the given list of values
- not_in:foo,bar - The field under validation must not be included in the given list of values
- integer - The field under validation must be an integer
- string - The field under validation must be a string
- max:value -  must be less than or equal a maximum value.
            Strings and arrays are evaluated based in size
- min:value -  must be higher than or equal a minimum value.
            Strings and arrays are evaluated based in size
- regex:pattern - The field under validation must match the given regular expression.
- size:value - The field under validation must have a size matching the given value,
          represented by the number of chars if integer or string and the count function for arrays

### Exemplos

Por meio de rotas já definidas, é possível realizar o teste das funções de acesso a um banco
de dados mysql local. Para isso, os seguintes passos devem ser realizados:

- Instalar um banco mysql local
- Editar o campo 'default_mysql_test' no arquivo 'database.ini' para realizar o apontamento correto ao banco local
- Criar uma database chamada 'test_db' (ou criar com qualquer outro nome e alterar em database.ini)
- Criar uma tabela chamada 'test_table' com uma coluna de chave primária e as seguintes colunas: 'column1', 'column2', 'column3'

Feitos os passos descritos acima, as funções CRUD podem ser utilizadas por meio das seguintes rotas:

/examples/insert  
/examples/select  
/examples/update  
/examples/delete  
/examples/paginate
/examples/validate (não necessária a configuração de banco de dados)

O código desses exemplos pode ser encontrado nos arquivos ExamplesController e ExamplesModel.
