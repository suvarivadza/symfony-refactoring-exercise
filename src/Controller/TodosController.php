<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TodosController extends AbstractController
{

    /**
     * Show completed and uncompleted items
     *
     * @param Connection $connection
     * @param Request $request
     * @return Response
     */
    public function showTodos(Connection $connection, Request $request): Response
    {
        $get_all = (int) $request->query->get('all');

        $todos = $connection->fetchAll('SELECT t.* FROM todos t' . (!$get_all ? ' WHERE completed = 0' : ''));

        return $this->render('showTodos.html.twig', ['todos' => $todos, 'get_all' => $get_all]);
    }

    /**
     * @param Connection $connection
     * @param Request $request
     * @param int $status
     * @return Response
     * @throws \Doctrine\DBAL\DBALException
     */
    public function toggleTodoCompleteness(Connection $connection, Request $request, int $status): Response
    {
        $connection->executeQuery('UPDATE todos SET completed = ' . (int) $status . ' WHERE id = ' . (int) $request->query->get('id'));

        return $this->redirect('/');
    }

}
