<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Todos;

class TodosController extends AbstractController
{

    /**
     * @param EntityManagerInterface $entityManager
     * @param int|null $all
     * @return Response
     */
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

    /**
     * @param EntityManagerInterface $entityManager
     * @param int $status
     * @param int $id
     * @return Response
     */
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

}
