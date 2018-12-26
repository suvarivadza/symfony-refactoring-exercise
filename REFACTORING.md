## Refactoring

Дублирующийся код (autoload и всё, что касается Dotenv) из `public/index.php` и `bin/console` выносится в новый файл `config/bootstrap.php`, который затем подключается через require в вышеуказанные оба файла. Конструкция `\__DIR__.'../` заменяется на `dirname(\__DIR__)`.

`src/Kernel.php`: т.к. текущая версия `symfony/dependency-injection` > 4.0, строку 
  `$container->setParameter('container.autowiring.strict_mode', true);` можно убрать.
  
`src/controller/TodosController.php`: обращение напрямую к $_GET не является хорошей практикой, поэтому оно заменяется на обращение через HttpFoundation Component (Request). Заодно при помощи Response того же компонента типизируется return у всех методов класса.
Код внутри метода `showTodos` оптимизируется применением тернарного оператора.
Кроме этого, имеет смысл передать в шаблон переменную `all` для того, что бы сделать ссылку-переключатель "Show completed" в `templates/showTodos.html.twig` более информативной и менять её текст в зависимости от запроса -- Show all / Show completed only
```
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
...
public function showTodos(Connection $connection, Request $request): Response
    {
        $get_all = (int) $request->query->get('all');
    
        $todos = $connection->fetchAll('SELECT t.* FROM todos t' . (!$get_all ? ' WHERE completed = 0' : ''));
    
        return $this->render('showTodos.html.twig', ['todos' => $todos, 'get_all' => $get_all]);
    }
```
Кода шаблона немного оптимизирован за счет вынесения тэгов `<li>` за пределы `if else`.

Кстати, в оригинальном коде проверка `$_GET['all'] == '1'` является избыточной, т.к. значение этой переменной нигде не используется, достаточно просто проверять её на существование.

В том же контроллере, два идентичных по функционалу метода `completeTodo` и `uncompleteTodo`, заменяются на универсальный `toggleTodoCompleteness`, который и переключает `complete` в 0 или 1 в зависимости от роутинга. 

config/routes.yaml:
```
completeTodo:
  path: /complete
  controller: App\Controller\TodosController::toggleTodoCompleteness
  defaults:
    status: 1

uncompleteTodo:
  path: /uncomplete
  controller: App\Controller\TodosController::toggleTodoCompleteness
  defaults:
    status: 0
```

src/controller/TodosController.php:
```
public function toggleTodoCompleteness(Connection $connection, Request $request, int $status): Response
    {
        $connection->executeQuery('UPDATE todos SET completed = ' . (int) $status . ' WHERE id = ' . (int) $request->query->get('id'));

        return $this->redirect('/');
    }
```

That's all.