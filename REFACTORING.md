## Refactoring

Дублирующийся код (autoload и всё, что касается Dotenv) из `public/index.php` и `bin/console` выносится в новый файл `config/bootstrap.php`, который затем подключается через require в вышеуказанные оба файла. Конструкция `\__DIR__.'../` заменяется на `dirname(\__DIR__)`.

`src/Kernel.php`: т.к. текущая версия `symfony/dependency-injection` > 4.0, строку 
  `$container->setParameter('container.autowiring.strict_mode', true);` можно убрать.
  
Контроллер `src/controller/TodosController.php`: обращение напрямую к $_GET не является хорошей практикой, поэтому (и еще для красоты) оно заменяется на адресацию через роутинг: `/all`, `/complete/{id}`, `/uncomplete/{id}`. 
При помощи Response из HttpFoundation Component типизируется return у всех методов класса.

Обращение к БД заменяется на Doctrine, в связи с этим появляется модель `src/Entity/Todos.php` и репозиторий `src/Repository/TodosRepository.php`

Кроме этого, имеет смысл передать в шаблон состояние переменной `all` для того, что бы сделать переключатель "Show completed" в `templates/showTodos.html.twig` более информативным и менять его текст в зависимости от запроса: Show all / Show completed only

Кстати, в оригинальном коде проверка `$_GET['all'] == '1'` является избыточной, т.к. значение этой переменной нигде не используется, достаточно просто проверять её на существование.

src/controller/TodosController.php:
```
...
public function showTodos(EntityManagerInterface $entityManager, int $all = null): Response
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(Todos::class);

        if ($all){
            $todos = $repo->findAll();
        } else {
            $todos = $repo->findBy(['completed' => 0]);
        }

        return $this->render('showTodos.html.twig', ['todos' => $todos, 'get_all' => $all]);
    }
```

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
...
public function toggleTodoCompleteness(EntityManagerInterface $entityManager, int $status, int $id): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $todos = $entityManager->getRepository(Todos::class)->find($id);

        if (!$todos) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }

        $todos->setCompleted($status);
        $entityManager->flush();

        return $this->redirect('/');
    }
```

Кода шаблона `templates/showTodos.html.twig` немного оптимизирован за счет вынесения тэгов `<li>` за пределы `if else`. 

The End.