<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\Type\User\SignupType;
use AppBundle\Form\Type\User\ConfirmType;
use AppBundle\Form\Type\User\ProfileType;
use AppBundle\Form\Type\User\ResetType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserController extends Controller
{
    use DoctrineController;

    /**
     * @Route("/login")
     * @Method("GET")
     * @Template
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error && $error->getMessageKey() === 'Invalid credentials.') {
            $error = "Email or password is incorrect";
        }
        return compact('lastUsername', 'error');
    }

    /**
     * @Route("/signup")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function signupAction(Request $request)
    {
        $form = $this->createForm(new SignupType(), $user = new User());

        $form->handleRequest($request);
        if (!$form->isValid()) {
            return ['form' => $form->createView()];
        }

        $same = $this->repo('AppBundle:User')->findOneBy(['email' => $user->getEmail()]);
        if (null !== $same and $same->isConfirmed()) {
            $form->get('email')->addError(new FormError("Email {$user->getEmail()} is already confirmed"));
            return ['form' => $form->createView()];
        }

        if (null !== $same) {
            $this->get('mail')->user($same, 'activate_email', [
                'link' => $this->generateUrl('app_user_confirm', ['token' => $same->getConfirmationToken()], true),
            ]);
            $this->addFlash('info', "Confirmation email was successfully resent to {$same->getEmail()}");
            return ['form' => $form->createView()];
        }

        $user->regenerateConfirmationToken();
        $this->persist($user);
        $this->flush();

        $this->get('mail')->user($user, 'activate_email', [
            'link' => $this->generateUrl('app_user_confirm', ['token' => $user->getConfirmationToken()], true),
        ]);

        $this->addFlash('success', "You should receive confirmation email shortly");
        return $this->redirect($this->generateUrl('app_user_login'));
    }

    /**
     * @Route("/confirm/{token}")
     * @Method({"GET", "POST"})
     * @ParamConverter("user", class="AppBundle:User", options={"mapping": {"token": "confirmationToken"}})
     * @Template
     */
    public function confirmAction(Request $request, User $user)
    {
        $form = $this->createForm(new ConfirmType(), $user);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            return ['form' => $form->createView(), 'token' => $user->getConfirmationToken()];
        }

        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword($user->getPlainPassword(), $user->getSalt()));
        $user->confirm();
        $this->persist($user);
        $this->flush();

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $this->addFlash('success', "The user {$user} was successfully confirmed");
        return $this->redirect($this->generateUrl('app_user_profile'));
    }

    /**
     * @Route("/profile")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function profileAction(Request $request)
    {
        $user = clone $this->getUser(); // prevent user change in session
        $form = $this->createForm(new ProfileType(), $user);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            return ['form' => $form->createView()];
        }

        $em = $this->getDoctrine()->getManager();
        if ($user->getPlainPassword()) {
            $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
            $user->setPassword($encoder->encodePassword($user->getPlainPassword(), $user->getSalt()));
        }
        $this->persist($em->merge($user));
        $this->flush();

        $this->addFlash('success', "Your profile was updated successfully");
        return $this->redirect($this->generateUrl('app_user_profile'));
    }

    /**
     * @Route("/reset")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function resetAction(Request $request)
    {
        $form = $this->createForm(new ResetType());
        $form->handleRequest($request);
        if (!$form->isValid()) {
            return ['form' => $form->createView()];
        }

        $email = $form->get('email')->getData();
        $user = $this->repo('AppBundle:User')->findOneByEmail($email);

        if (!$user) {
            $form->get('email')->addError(new FormError("User with this email not found"));
            return ['form' => $form->createView()];
        }

        // @TODO: expiration date may be useful
        $user->regenerateConfirmationToken();
        $this->persist($user);
        $this->flush();

        // @TODO: captcha after 3 failed attempts
        $this->get('mail')->user($user, 'activate_email', [
            'link' => $this->generateUrl('app_user_confirm', ['token' => $user->getConfirmationToken()], true),
        ]);

        $this->addFlash('success', "Password reset email was sent to {$user->getEmail()}");
        return $this->redirect($this->generateUrl('app_user_login'));
    }
}
