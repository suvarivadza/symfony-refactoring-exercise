<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TodosController extends AbstractController
{

    /**
     * @param Connection $connection
     * @param int|null $all
     * @return Response
     */
    public function showTodos(Connection $connection, int $all = null): Response
    {
        $todos = $connection->fetchAll('SELECT t.* FROM todos t' . (!$all ? ' WHERE completed = 0' : ''));

        return $this->render('showTodos.html.twig', ['todos' => $todos, 'get_all' => $all]);
    }

    /**
     * @param Connection $connection
     * @param int $status
     * @param int $id
     * @return Response
     * @throws \Doctrine\DBAL\DBALException
     */
    public function toggleTodoCompleteness(Connection $connection, int $status, int $id): Response
    {
        $connection->executeUpdate('UPDATE todos SET completed = ' . (int) $status . ' WHERE id = ' . (int) $id);

        return $this->redirect('/');
    }

}
