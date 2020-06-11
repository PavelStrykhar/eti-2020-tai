<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditingAccountType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/account", name="account.")
 */
class AccountController extends AbstractController
{
    /**
     * @Route("/settings", name="settings")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserInterface $user
     * @return Response
     */
    public function updateUser(Request $request, UserPasswordEncoderInterface $passwordEncoder, UserInterface $user)
    {
        $id = $this->getUser()->getId();

        $form = $this->createForm(EditingAccountType::class);
        $form->handleRequest($request);

        $entityManager = $this->getDoctrine()->getManager();
        $userManager = $entityManager->getRepository(User::class)->find($id);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->getData()->getUsername();
            $password = $form->getData()->getPassword();
            $password = $passwordEncoder->encodePassword($user, $password);

            $userManager->setUsername($username);
            $userManager->setPassword($password);
            $entityManager->flush();

            $this->addFlash('notice','Your changes were saved!');
        }
//        var_dump($form);


//        $user->setUsername('111111');
//        $entityManager->flush();
////        var_dump($user);

        return $this->render('account/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
