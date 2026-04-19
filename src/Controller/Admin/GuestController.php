<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/guests', name: 'admin_guests_')]
#[IsGranted('ROLE_ADMIN')]
class GuestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $userRepository->findGuestAccounts(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(GuestType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = bin2hex(random_bytes(8));
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_USER']);
            $user->setBlocked(false);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Invité créé. Communiquez-lui ce mot de passe provisoire (à changer après première connexion) : %s',
                $plainPassword
            ));

            return $this->redirectToRoute('admin_guests_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/block', name: 'block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleBlock(Request $request, int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user instanceof User || $user->isAdmin()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('guest_block_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setBlocked(!$user->isBlocked());
        $this->entityManager->flush();

        $this->addFlash('success', $user->isBlocked() ? 'Invité bloqué.' : 'Invité débloqué.');

        return $this->redirectToRoute('admin_guests_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user instanceof User || $user->isAdmin()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('guest_delete_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        foreach ($user->getMedias()->toArray() as $media) {
            $path = $projectDir.'/public/'.$media->getPath();
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Invité et médias associés supprimés.');

        return $this->redirectToRoute('admin_guests_index');
    }
}
