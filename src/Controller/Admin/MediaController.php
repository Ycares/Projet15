<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $criteria = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $this->entityManager->getRepository(Media::class)->findBy(
            $criteria,
            ['id' => 'ASC'],
            25,
            25 * ($page - 1)
        );
        $total = $this->entityManager->getRepository(Media::class)->count([]);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($this->getUser());
            }
            $uploadedFile = $media->getFile();
            if (null !== $uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads';
                $filename = md5(uniqid('', true)).'.'.($uploadedFile->guessExtension() ?: 'bin');
                $uploadedFile->move($uploadDir, $filename);
                $media->setPath('uploads/'.$filename);
            }
            $this->entityManager->persist($media);
            $this->entityManager->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id): Response
    {
        $media = $this->entityManager->getRepository(Media::class)->find($id);
        if (null === $media) {
            throw $this->createNotFoundException();
        }

        // Un invité ne peut supprimer que ses propres médias.
        if (!$this->isGranted('ROLE_ADMIN')) {
            $currentUser = $this->getUser();
            if (
                !$currentUser instanceof User
                || null === $media->getUser()
                || $media->getUser()->getId() !== $currentUser->getId()
            ) {
                throw $this->createNotFoundException();
            }
        }

        $path = $this->getParameter('kernel.project_dir').'/public/'.$media->getPath();
        $this->entityManager->remove($media);
        $this->entityManager->flush();
        if (is_file($path)) {
            unlink($path);
        }

        return $this->redirectToRoute('admin_media_index');
    }
}
