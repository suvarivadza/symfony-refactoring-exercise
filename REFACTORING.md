## Refactoring

Дублирующийся код (autoload и всё, что касается Dotenv) из `public/index.php` и `bin/console` выносится в новый файл `config/bootstrap.php`, который затем подключается через require в вышеуказанные оба файла. Конструкция `\__DIR__.'../` заменяется на `dirname(\__DIR__)`.

`src/Kernel.php`: т.к. текущая версия `symfony/dependency-injection` > 4.0, строку 
  `$container->setParameter('container.autowiring.strict_mode', true);` можно убрать.
  
`src/controller/TodosController.php`: обращение напрямую к $_GET не является хорошей практикой, поэтому (и еще для красоты) оно заменяется на адресацию через роутинг: `/all`, `/complete/{id}`, `/uncomplete/{id}`. 
При помощи Response из HttpFoundation Component типизируется return у всех методов класса.
Код `if/else` внутри метода `showTodos` оптимизируется применением тернарного оператора.
Кроме этого, имеет смысл передать в шаблон состояние переменной `all` для того, что бы сделать ссылку-переключатель "Show completed" в `templates/showTodos.html.twig` более информативной и менять её текст в зависимости от запроса -- Show all / Show completed only
```
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
...
    public function showTodos(Connection $connection, int $all = null): Response
    {
        $todos = $connection->fetchAll('SELECT t.* FROM todos t' . (!$all ? ' WHERE completed = 0' : ''));
        return $this->render('showTodos.html.twig', ['todos' => $todos, 'get_all' => $all]);
    }
```
Кода шаблона немного оптимизирован за счет вынесения тэгов `<li>` за пределы `if else`.

Кстати, в оригинальном коде проверка `$_GET['all'] == '1'` является избыточной, т.к. значение этой переменной нигде не используется, достаточно просто проверять её на существование.

В том же контроллере, два идентичных по функционалу метода `completeTodo` и `uncompleteTodo`, заменяются на универсальный `toggleTodoCompleteness`, который и переключает `complete` в 0 или 1 в зависимости от роутинга. 

config/routes.yaml:
```
completeTodo:
  path: /complete/{id}
  controller: App\Controller\TodosController::toggleTodoCompleteness
  defaults:
    status: 1
  requirements:
    id: '\d+'

uncompleteTodo:
  path: /uncomplete/{id}
  controller: App\Controller\TodosController::toggleTodoCompleteness
  defaults:
    status: 0
  requirements:
    id: '\d+'
```

src/controller/TodosController.php:
```
    public function toggleTodoCompleteness(Connection $connection, int $status, int $id): Response
    {
        $connection->executeUpdate('UPDATE todos SET completed = ' . (int) $status . ' WHERE id = ' . (int) $id);
        return $this->redirect('/');
    }
```

Если бы код внутри контроллера был более объемный, имело бы смысл вынести ту часть, которая работает с БД, в модель. 
