<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\CategorieArticle;
use App\Entity\Category;
use App\Entity\ImageArticle;
use App\Repository\ArticleRepository;
use App\Repository\CategorieArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CategorieArticleController extends AbstractController
{
    #[Route('/api/categories', name: 'api_get_categories', methods: ['GET'])]
    public function getCategories(CategorieArticleRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->getId(),
                'nom' => $category->getNom(),
            ];
        }

        return $this->json($result);
    }

    #[Route('/api/categorie', name: 'api_create_category', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'])) {
            return $this->json(['error' => 'The name field is required'], 400);
        }

        $category = new CategorieArticle();
        $category->setNom($data['nom']);

        $em->persist($category);
        $em->flush();

        return $this->json(['message' => 'Category created successfully', 'id' => $category->getId()], 201);
    }

    #[Route('/api/categorie/{id}', name: 'api_update_category', methods: ['PUT'])]
    public function updateCategory(Request $request, CategorieArticle $category, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'])) {
            return $this->json(['error' => 'The name field is required'], 400);
        }

        $category->setNom($data['nom']);
        $em->flush();

        return $this->json(['message' => 'Category updated successfully']);
    }

    #[Route('/api/categorie/{id}', name: 'api_delete_category', methods: ['DELETE'])]
    public function deleteCategory(CategorieArticle $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();

        return $this->json(['message' => 'Category deleted successfully']);
    }

    #[Route('/api/articles', name: 'api_get_articles', methods: ['GET'])]
    public function getArticles(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();
        $result = [];

        foreach ($articles as $article) {
            $images = [];
            foreach ($article->getImages() as $image) {
                $images[] = $image->getUrl();
            }
            $result[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'categorie' => [
                    'id' => $article->getCategorie()->getId(),
                    'nom' => $article->getCategorie()->getNom(),
                ],
                'auteur' => $article->getAuteur()->getUsername(),
                'images' => $images,
            ];
        }

        return $this->json($result);
    }

    #[Route('/api/article', name: 'api_create_article', methods: ['POST'])]
    public function createArticle(
        Request $request,
        EntityManagerInterface $em,
        CategorieArticleRepository $categoryRepository,
        UserRepository $userRepository,
        string $uploadDir = 'uploads/articles'
    ): JsonResponse {

        $title = $request->get('title');
        $content = $request->get('content');
        $categorieId = $request->get('categorie');
        $userId = $request->get('user_id');

        if (!$title || !$content || !$categorieId || !$userId) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }
        $user = $userRepository->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        $categorie = $categoryRepository->find($categorieId);
        if (!$categorie) return $this->json(['error' => 'Category not found'], 404);



        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setAuteur($user);
        $article->setCategorie($categorie);
        $article->setCreatedAt(new \DateTimeImmutable());

        /** @var UploadedFile[] $files */
        $files = $request->files->all()['images'] ?? [];


        foreach ($files as $file) {

            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
                return $this->json(['error' => 'Only JPEG, PNG, and WEBP images allowed'], 400);
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->json(['error' => 'Image too large. Max 2MB'], 400);
            }



            $filename = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadDir, $filename);

            $image = new ImageArticle();
            $image->setUrl("/$uploadDir/$filename");
            $article->addImage($image);
        }

        $em->persist($article);
        $em->flush();

        return $this->json(['message' => 'Article created successfully', 'id' => $article->getId()], 201);
    }

    #[Route('/api/article/{id}', name: 'api_update_article', methods: ['POST'])]
    public function updateArticle(
        Request $request,
        Article $article,
        EntityManagerInterface $em,
        CategorieArticleRepository $categoryRepository,
        string $uploadDir = 'uploads/articles'
    ): JsonResponse {

        $title = $request->get('title');
        $content = $request->get('content');
        $categorieId = $request->get('categorie');

        if (!$title || !$content || !$categorieId) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $categorie = $categoryRepository->find($categorieId);
        if (!$categorie) {
            return $this->json(['error' => 'Category not found'], 404);
        }
        $article->setTitle($title);
        $article->setContent($content);
        $article->setCategorie($categorie);

        // Supprimer les anciennes images
        foreach ($article->getImages() as $oldImage) {
            $em->remove($oldImage);
            // Optionnel : Supprimer aussi le fichier du disque
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . $oldImage->getUrl();
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        /** @var UploadedFile[] $files */
        $files = $request->files->all()['images'] ?? [];

        foreach ($files as $file) {

            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
                return $this->json(['error' => 'Only JPEG, PNG, and WEBP images allowed'], 400);
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->json(['error' => 'Image too large. Max 2MB'], 400);
            }


            $filename = uniqid() . '.' . $file->guessExtension();
            $file->move($uploadDir, $filename);

            $image = new ImageArticle();
            $image->setUrl("/$uploadDir/$filename");
            $article->addImage($image);
        }


        $em->flush();

        return $this->json(['message' => 'Article updated successfully']);
    }

    #[Route('/api/article/{id}', name: 'api_delete_article', methods: ['DELETE'])]
    public function deleteArticle(Article $article, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($article);
        $em->flush();

        return $this->json(['message' => 'Article deleted successfully']);
    }
}
