<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Comments;
use App\Entity\User;
use App\Form\AddPostType;
use App\Form\CommentType;
use App\Form\EditingAccountType;
use App\Form\EditPostType;
use App\Repository\BlogPostRepository;
use App\Repository\CommentsRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/", name="post.")
 */
class EtiController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    public function blogHomepage()
    {
        return $this->render('eti/blog/homepage.html.twig');
    }

    /**
     * @Route("/first/page", name="first_page")
     * @param TranslatorInterface $translator
     * @return Response
     * @throws Exception
     */
    public function randomNumber(TranslatorInterface $translator)
    {
        $number = random_int(0, 100);

        return $this->render('eti/blog/first_page.html.twig', [
            'number' => $number,
            'translated_php' => $translator->trans('Translated string'),
            'translated_php_pl' => $translator->trans('Translated string', [], 'messages', 'pl_PL')
        ]);
    }


    /**
     * @Route("posts/list", name="post_listing")
     *
     * @param BlogPostRepository $blogPostRepository
     * @return Response
     */
    public function listBlogPosts(BlogPostRepository $blogPostRepository)
    {
        $articles = $blogPostRepository->findBy([
            'is_visible' => true
        ]);

        if ($this->getUser() != null) {
            $user = $this->getUser()->getId();
        } else $user = null;

        return $this->render('eti/blog/posts.html.twig', [
            'articles' => $articles,
            'posts' => "Posts",
            'user' => $user
        ]);
    }


    /**
     * @Route("posts/view/{id}", name="post_details")
     *
     * @param Request $request
     * @param BlogPost $blogPost
     * @param CommentsRepository $commentsRepository
     * @return Response
     */
    public function postDetails(Request $request, BlogPost $blogPost, CommentsRepository $commentsRepository)
    {
        if ($blogPost->getIsVisible() == false && $this->getUser() == null) {
            return $this->render('errors/unauthorized.html.twig');
        }

//        if ($blogPost->getIsAddingCommentAnonymous() == true || $blogPost->getIsAddingComment() == true && $this->getUser() != null) {
//
//        }
        $commentString = explode('/', $blogPost->getComments());
        $numberOfComments = sizeof($commentString)-1;

        $idComments = [];
        for ($i = 0; $i < $numberOfComments; $i++) {
            array_push($idComments, $commentString[$i]);
        }

        $allComments = $commentsRepository->findBy([
            'id' => $idComments
        ], [
            'add_date' => 'DESC'
        ]);

        $newComment = new Comments();
        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->getData()->getcontent();

            if ($this->getUser() == true){
                $user = $this ->getUser()->getUsername();
                $idUser = $this->getUser()->getId();
            } else {
                $user = null;
                $idUser = null;
            }


            $newComment->setUsername($user);
            $newComment->setContent($content);
            $newComment->setAddDate(new \DateTime());
            $newComment->setUserId($idUser);


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newComment);
            $entityManager->flush();

            $idComment = $newComment->getId();
            $idPost = $blogPost->getId();
            $entityManagerBlogPost = $this->getDoctrine()->getManager();
            $managerPost = $entityManagerBlogPost->getRepository(BlogPost::class)->find([
                'id' => $idPost
            ]);
            if ($blogPost->getComments() == null) {
                $updateCommentInPost = $idComment . '/';
            } else {
                $updateCommentInPost = $blogPost->getComments() . $idComment . '/';
            }
            $managerPost->setComments($updateCommentInPost);
            $entityManagerBlogPost->flush();

            $this->addFlash(
                'notice',
                'Your comment were added!'
            );
        }

        return $this->render('eti/blog/post_view.html.twig', [
            'article' => $blogPost,
            'comments' => $allComments,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/hidden_content", name="hidden_content")
     * @param UserRepository $userRepository
     * @return Response
     */
    public function hiddenContent(UserRepository $userRepository)
    {
        $userId = $this->getUser()->getId();
        $userName = $this->getUser()->getUsername();

        return $this->render('eti/blog/hidden_content.html.twig', [
            'userId' => $userId,
            'userName' => $userName
        ]);
    }

    /**
     * @Route("/posts/for_users_logged", name="for_logged_users")
     * @param BlogPostRepository $blogPostRepository
     * @return Response
     */
    public function postLoggedUsers(BlogPostRepository $blogPostRepository)
    {
        $id = $this->getUser()->getId();
        $articles = $blogPostRepository->findBy([
            'is_visible' => false
        ]);

        return $this->render('eti/blog/posts.html.twig', [
            'articles' => $articles,
            'posts' => "Hidden posts",
            'user' => $id
        ]);
    }

    /**
     * @Route("/post/add", name="add_post")
     * @param Request $request
     * @return Response
     */
    public function addPost(Request $request)
    {
        $post = new BlogPost();
        $id = $this->getUser()->getId();

        $form = $this->createForm(AddPostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $title = $form->getData()->getTitle();
            $summary = $form->getData()->getSummary();
            $content = $form->getData()->getContent();
            $isVisible = $form->getData()->getIsVisible();
            $isAddingComment = $form->getData()->getIsAddingComment();
            $isAddingCommentAnonymous = $form->getData()->getIsAddingCommentAnonymous();

            $post->setTitle($title);
            $post->setSummary($summary);
            $post->setContent($content);
            $post->setCreationDate(new \DateTime());
            $post->setCreatorId($id);
            $post->setIsVisible($isVisible);
            $post->setIsAddingComment($isAddingComment);
            $post->setIsAddingCommentAnonymous($isAddingCommentAnonymous);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Your post were added!!'
            );
        }

        return $this->render('eti/blog/add_post.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("post/user", name="user_post")
     * @param Request $request
     * @param BlogPostRepository $blogPostRepository
     * @return Response
     */
    public function userPost(Request $request, BlogPostRepository $blogPostRepository)
    {
        $id = $this->getUser()->getId();
        $articles = $blogPostRepository->findBy([
            'creator_id' => $id
        ]);

        return $this->render('eti/blog/posts.html.twig', [
            'articles' => $articles,
            'posts' => "Your posts",
            'user' => $id
        ]);
    }

    /**
     * @Route("post/edit/{id}", name="edit_post")
     * @param BlogPost $blogPost
     * @param Request $request
     * @return Response
     */
    public function editPost(BlogPost $blogPost, Request $request)
    {
        $user_id = $this->getUser()->getId();
        $creator_id = $blogPost->getCreatorId();

        if ($user_id != $creator_id) {
            return $this->render('errors/unauthorized.html.twig');
        }

        $postId = $blogPost->getId();

        $entityManager = $this->getDoctrine()->getManager();
        $postManager = $entityManager->getRepository(BlogPost::class)->find($postId);


        $form = $this->createForm(EditPostType::class, $postManager);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $title = $form->getData()->getTitle();
            $summary = $form->getData()->getSummary();
            $content = $form->getData()->getContent();
            $isVisible = $form->getData()->getIsVisible();
            $isAddingComment = $form->getData()->getIsAddingComment();
            $isAddingCommentAnonymous = $form->getData()->getIsAddingCommentAnonymous();

            $postManager->setTitle($title);
            $postManager->setSummary($summary);
            $postManager->setContent($content);
            $postManager->setIsVisible($isVisible);
            $postManager->setIsAddingComment($isAddingComment);
            $postManager->setIsAddingCommentAnonymous($isAddingCommentAnonymous);

            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );
        }

        return $this->render('eti/blog/edit_post.html.twig', [
            'form' => $form->createView(),

        ]);
    }
}