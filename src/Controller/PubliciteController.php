<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormError;
use DateTime;
use App\Repository\PubliciteRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\publicite;
use App\Entity\enduser;
use App\Form\PubliciteType;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class PubliciteController extends AbstractController
{
    #[Route('/publicite', name: 'app_publicite')]
    public function index(): Response
    {
        return $this->render('publicite/index.html.twig', [
            'controller_name' => 'PubliciteController',
        ]);
    }
    
    #[Route('/publicite/showPub', name: 'publicite_show')]
    public function showPub(Request $request, PubliciteRepository $repository): Response
    {
        // Fetch the search query from the request
        $query = $request->query->get('query');
    
        // Fetch the current page number from the query parameters
        $currentPage = $request->query->getInt('page', 1);
    
        // Fetch the total number of pages (replace with your actual logic)
        $totalPages = 10; // Replace this with your actual calculation
    
        // If a search query is provided, filter publicités based on the title
        if ($query) {
            $publicites = $repository->findByTitre($query); // Replace with appropriate method
        } else {
            // If no search query is provided, fetch all publicités
            $publicites = $repository->findAll();
        }
    
        return $this->render('publicite/showPub.html.twig', [
            'publicites' => $publicites, // Corrected variable name
            'query' => $query,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }
    #[Route('/publicite/deletePub/{i}', name: 'deletePub')]
    public function deletePub($i, PubliciteRepository $rep, ManagerRegistry $doctrine): Response
    {
        $publicite = $rep->find($i);
    
        if (!$publicite) {
            throw $this->createNotFoundException('publicite not found');
        }
    
        $em = $doctrine->getManager();
        $em->remove($publicite);
        $em->flush();
    
        // Redirect to a success page or return a response as needed
        // For example:
        return $this->redirectToRoute('publicite_show');
    }
    #[Route('/publicite/ajouterPub', name: 'ajouterPub')]     
    public function ajouterPub(ManagerRegistry $doctrine, Request $req): Response
    {
        $publicite = new publicite();
        $userId = 48; 
        $user = $this->getDoctrine()->getRepository(enduser::class)->find($userId);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
    
        $publicite->setIdUser($user);
        
        // Set the current date to the date_a property
       
    
        $form = $this->createForm(PubliciteType::class, $publicite);
        $form->handleRequest($req);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Set the image_a field
            $image = $form->get('image_pub')->getData();
            if ($image) {
                // Handle image upload and persist its filename to the database
                $fileName = uniqid().'.'.$image->guessExtension();
                try {
                    $image->move($this->getParameter('uploadsDirectory'), $fileName);
                    $publicite->setImagePub($fileName);
                } catch (FileException $e) {
                    // Handle the exception if file upload fails
                    // For example, log the error or display a flash message
                }
            }
    
            // Get the entity manager
            $em = $doctrine->getManager();
    
       
            $em->persist($publicite);
            $em->flush();

            return $this->redirectToRoute('app_actualite');
           
        
        }
    
        return $this->render('publicite/ajouterPub.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[Route('/publicite/modifierPub/{id}', name: 'modifierPub')]

    public function modifierPub($id, ManagerRegistry $doctrine, Request $request): Response

{
    $entityManager = $doctrine->getManager();
    $publicite = $entityManager->getRepository(Publicite::class)->find($id);

    if (!$publicite) {
        throw $this->createNotFoundException('Publicité not found');
    }

    // Create the form for modifying the actualite
    $form = $this->createForm(PubliciteType::class, $publicite);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
    
        $image = $form->get('image_pub')->getData();
        if ($image) {
            // Handle image upload and persist its filename to the database
            $fileName = uniqid().'.'.$image->guessExtension();
            try {
                $image->move($this->getParameter('uploadsDirectory'), $fileName);
                $actualite->getImagePub($fileName);
            } catch (FileException $e) {
                // Handle the exception if file upload fails
                // For example, log the error or display a flash message
            }
        }

        // Persist the modified actualite object to the database
        $entityManager->flush();

        // Redirect to a success page or display a success message
        // For example:
        return $this->redirectToRoute('app_publicite');
    }

    return $this->render('publicite/modifierPub.html.twig', [
        'form' => $form->createView(),
        'publicite' => $publicite,
    ]);
}
    #[Route('/showPubCitoyen', name: 'app_publicite')]
public function index1(PubliciteRepository $repository): Response
{
    $publicites = $repository->findAll(); // Fetch all actualités from the repository

    return $this->render('publicite/showPubCitoyen.html.twig', [
        'publicites' => $publicites,
        
    ]);
}
#[Route('/showPubResponsable', name: 'app_publiciteResponsable')]
public function index2(PubliciteRepository $repository): Response
{
    $publicites = $repository->findAll(); // Fetch all actualités from the repository

    return $this->render('publicite/showPubResponsable.html.twig', [
        'publicites' => $publicites,
        
    ]);
}

#[Route('/Payment', name: 'payment')]
public function payment(Request $request): Response
{
    // Récupérer la durée de l'offre et le montant correspondant depuis la requête ou la base de données
    $offre = $request->get('offre_pub');
    $montant = 0; // Initialiser le montant

    // Définir le montant en fonction de la durée de l'offre
    switch ($offre) {
        case '3 mois':
            $montant = 1470; // 50 DT en centimes (par exemple, 50,00 DT)
            break;
        case '6 mois':
            $montant = 2647; // 90 DT en centimes
            break;
        case '9 mois':
            $montant = 3823; // 130 DT en centimes
            break;
        // Ajoutez d'autres cas selon vos besoins
    }

    // Configurer Stripe avec votre clé secrète
    Stripe::setApiKey('sk_test_51OpeMeI3VcdValufdQQI5nr0PLI1jmQ9YCCa6Xu4ozS5Qv9IBoaTSvqMtzZXaZf0edfdRkNVVLixMKfo8CtYx3PW00MLcbGNSd');

    try {
        // Créer un PaymentIntent avec les détails du paiement
        $paymentIntent = PaymentIntent::create([
            'amount' => $montant,
            'currency' => 'usd',
        ]);

        // Si le paiement réussit, afficher un message de succès
        $paymentStatus = 'Paiement réussi. ID du paiement : ' . $paymentIntent->id;
    } catch (\Exception $e) {
        // Si une erreur survient lors du traitement du paiement, afficher le message d'erreur
        $paymentStatus = 'Le paiement a échoué. Erreur : ' . $e->getMessage();
    }

    // Rendre le template Twig avec le statut du paiement
    return $this->render('publicite/Payment.html.twig', [
        'paymentStatus' => $paymentStatus,
    ]);
}

}
