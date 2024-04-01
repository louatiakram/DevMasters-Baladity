<?php

namespace App\Controller;

use App\Entity\enduser;
use App\Form\LoginType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use function PHPUnit\Framework\equalTo;

class LoginController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    #[Route('/login', name: 'app_login')]
    public function login(ManagerRegistry $doctrine, Request $request,AuthenticationUtils $authenticationUtils): Response
    {

        // Create an instance of the Enduser entity
        $user = new enduser();

        // Get any authentication error message
        $error = $authenticationUtils->getLastAuthenticationError();

        // Create the login form
        $form = $this->createForm(LoginType::class, $user);
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Retrieve the submitted data from the form
            $emailSaisie = $form->get('email_user')->getData();
            $passwordSaisie = $form->get('password')->getData();
            

            // Retrieve the user from the database based on the provided email
            $userRepository = $doctrine->getRepository(enduser::class);
            $user = $userRepository->findOneBy(['email_user' => $emailSaisie]);

            // Check if a user with the provided email exists
            if ($user) {
                // Verify if the password from the form matches the hashed password stored in the database
                $hashedPassword = $user->getPassword();
                if ($passwordSaisie == $hashedPassword) {
                    // Password is correct, store user ID in session
                    $request->getSession()->set('user_id', $user->getIdUser());
                    // Password is correct, redirect the user to the app_main route
                    return $this->redirectToRoute('app_main');
                } else {
                    // Add a form error for incorrect password
                    $form->addError(new FormError('Invalid email or password.'));
                }
            } else {
                // Add a form error for user not found
                $form->addError(new FormError('User not found.'));
            }
        }

        // Render the login form
        return $this->render('login/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }
}
