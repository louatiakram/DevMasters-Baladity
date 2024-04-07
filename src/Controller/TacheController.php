<?php

namespace App\Controller;

use App\Entity\enduser;
use App\Entity\tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use App\Entity\CommentaireTache;
use App\Form\CommentaireTacheType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;


class TacheController extends AbstractController
{
    #[Route('/tache', name: 'tache_list')]
    public function list(Request $request, TacheRepository $repository, PaginatorInterface $paginator): Response
    {
        // Fetch all tasks from the repository
        $query = $repository->createQueryBuilder('t')
            ->orderBy('t.date_FT', 'ASC')
            ->getQuery();

        // Paginate the query
        $tasks = $paginator->paginate(
            $query, // Doctrine Query object
            $request->query->getInt('page', 1), // Page number
            2 // Limit per page
        );

        return $this->render('tache/list.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/tache/search', name: 'tache_search', methods: ['GET'])]
    public function search(TacheRepository $tacheRepository, Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        dump($query);

        $results = [];
        if ($query !== null) {
            $results = $tacheRepository->findByNom($query)->getQuery()->getResult();
        }

        $response = [];
        foreach ($results as $result) {
            $response[] = [
                'url' => $this->generateUrl('tache_detail', ['i' => $result->getIdT()]),
                'nom' => $result->getTitreT(),
            ];
        }

        return new JsonResponse($response);
    }

    #[Route('/tache/detail/{i}', name: 'tache_detail')]
    public function detail($i, TacheRepository $rep): Response
    {
        $userId = 50; // Assuming the user ID is 50
        $user = $this->getDoctrine()->getRepository(enduser::class)->find($userId);
        $tache = $rep->find($i);
        if (!$tache) {
            throw $this->createNotFoundException('Tache Existe Pas');
        }

        return $this->render('tache/detail.html.twig', [
            'tache' => $tache,
            'userId' => $userId,
        ]);
    }

    #[Route('/tache/detailfront/{i}', name: 'tache_detail_front')]
public function detailfront($i, Request $request, TacheRepository $rep): Response
{
    $userId = 49; // Assuming the user ID is 50
    $user = $this->getDoctrine()->getRepository(enduser::class)->find($userId);
    if (!$user) {
        throw $this->createNotFoundException('User Existe Pas');
    }
    
    $tache = $rep->find($i);
    if (!$tache) {
        throw $this->createNotFoundException('Tache Existe Pas');
    }

    // Create a new CommentaireTache entity
    $comment = new CommentaireTache();
    $comment->setIdUser($user);
    $commentForm = $this->createForm(CommentaireTacheType::class, $comment);

    // Handle the comment form submission
    $commentForm->handleRequest($request);
    if ($commentForm->isSubmitted() && $commentForm->isValid()) {
        // Associate the comment with the tache entity
        $comment->setIdT($tache);
        $comment->setDateC(new \DateTime()); // Set current date
        
        // Persist and flush the comment entity
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($comment);
        $entityManager->flush();

        // Redirect or return a response
        return $this->redirectToRoute('tache_detail_front', ['i' => $i]);
    }

    // Pass the comment form and tache details to the Twig template
    return $this->render('tache/detailfront.html.twig', [
        'tache' => $tache,
        'commentForm' => $commentForm->createView(),
        'userId' => $userId,
    ]);
}

    #[Route('/tache/add', name: 'tache_add')]
    public function add(Request $req, ManagerRegistry $doctrine): Response
    {
        $userId = 50; // Assuming the user ID is 50
        $user = $this->getDoctrine()->getRepository(enduser::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User Existe Pas');
        }

        $x = new tache();
        $x->setIdUser($user);

        $form = $this->createForm(TacheType::class, $x);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {

            $em = $doctrine->getManager();
            // Check if a task with the same titre_T and nom_Cat already exists
            $existingTask = $em->getRepository(tache::class)->findOneBy([
                'titre_T' => $x->getTitreT(),
                'nom_Cat' => $x->getNomCat(),
            ]);

            if ($existingTask) {
                $form->addError(new FormError('Une tâche avec le même titre et la même catégorie existe déjà !'));
            } else {

                // Handle file upload
                /** @var UploadedFile|null $pieceJointe */
                $pieceJointe = $form->get('pieceJointe_T')->getData();
                if ($pieceJointe) {
                    $originalFilename = pathinfo($pieceJointe->getClientOriginalName(), PATHINFO_FILENAME);
                    // Move the file to the uploads directory
                    try {
                        $uploadedFile = $pieceJointe->move(
                            $this->getParameter('uploadsDirectory'), // Use the parameter defined in services.yaml
                            $originalFilename . '.' . $pieceJointe->guessExtension()
                        );
                        $x->setPieceJointeT($uploadedFile->getFilename());
                    } catch (FileException $e) {
                    }
                }

                // Get the selected etat_T value from the form
                $selectedEtatT = $form->get('etat_T')->getData();

                // Set the etat_T property of the tache entity
                $x->setEtatT($selectedEtatT);

                $em = $doctrine->getManager();
                $em->persist($x);
                $em->flush();
                return $this->redirectToRoute('tache_list');
            }

        }
        return $this->renderForm('tache/add.html.twig', ['f' => $form,]);
    }


    #[Route('/tache/update/{i}', name: 'tache_update')]
    public function update($i, TacheRepository $rep, Request $req, ManagerRegistry $doctrine): Response
    {
        $x = $rep->find($i);
        $form = $this->createForm(TacheType::class, $x);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle file upload
            /** @var UploadedFile|null $pieceJointe */
            $pieceJointe = $form->get('pieceJointe_T')->getData();
            if ($pieceJointe) {
                $originalFilename = pathinfo($pieceJointe->getClientOriginalName(), PATHINFO_FILENAME);
                // Move the file to the uploads directory
                try {
                    $uploadedFile = $pieceJointe->move(
                        $this->getParameter('uploadsDirectory'), // Use the parameter defined in services.yaml
                        $originalFilename . '.' . $pieceJointe->guessExtension()
                    );
                    $x->setPieceJointeT($uploadedFile->getFilename());
                } catch (FileException $e) {
                }
            }
            // Get the selected etat_T value from the form
            $selectedEtatT = $form->get('etat_T')->getData();

            // Set the etat_T property of the tache entity
            $x->setEtatT($selectedEtatT);

            $em = $doctrine->getManager();
            $em->flush();
            return $this->redirectToRoute('tache_list');
        }

        return $this->renderForm('tache/add.html.twig', ['f' => $form,]);
    }

    #[Route('/tache/delete/{i}', name: 'tache_delete')]
    public function delete($i, TacheRepository $rep, ManagerRegistry $doctrine): Response
    {
        $xs = $rep->find($i);
        $em = $doctrine->getManager();
        $em->remove($xs);
        $em->flush();
        return $this->redirectToRoute('tache_list');
    }

    #[Route('/tache/listfront', name: 'tache_listfront')]
    public function listfront(Request $request, TacheRepository $repository): Response
    {
        // If no search query is provided, fetch all tasks with pagination
        $taches = $repository->findBy([], ['date_FT' => 'ASC']);
        //$taches = $this->getDoctrine()->getRepository(Tache::class)->findAll();

        return $this->render('tache/listfront.html.twig', [
            'taches' => $taches,
        ]);
    }

    #[Route('/update-tache-state/{tacheId}/{newState}', name: 'update_tache_state')]
    public function updateTacheState(Request $request, int $tacheId, string $newState): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $tache = $entityManager->getRepository(Tache::class)->find($tacheId);

        if (!$tache) {
            return new JsonResponse(['error' => 'Tache Existe Pas'], Response::HTTP_NOT_FOUND);
        }

        // Update etat_T attribute of the tache entity
        $tache->setEtatT($newState);

        try {
            $entityManager->flush(); // Save changes to the database
            return new JsonResponse(['message' => 'Tache state updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update tache state'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/tache/piechart', name: 'tache_piechart')]
    public function pieChart(TacheRepository $tacheRepository): Response
    {
        // Get the count of tasks done by each user
        $usersTasksCount = $tacheRepository->getUsersTasksCount();

        // Extract user names and task counts from the result
        $data = [];
        foreach ($usersTasksCount as $result) {
            $userName = $result['user_name'];
            $taskCount = $result['task_count'];
            $data[] = ['user_name' => $userName,
                'task_count' => $taskCount];
        }

        return $this->render('tache/piechart.html.twig', [
            'data' => $data // Pass data to twig template
        ]);
    }
}