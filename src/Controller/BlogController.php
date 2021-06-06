<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Events\CommentCreatedEvent;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/blog")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods={"GET"}, name="blog_index")
     * @Route("/rss.xml", defaults={"page": "1", "_format"="xml"}, methods={"GET"}, name="blog_rss")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods={"GET"}, name="blog_index_paginated")
     * @Cache(smaxage="10")
     *
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     * See https://symfony.com/doc/current/quick_tour/the_controller.html#using-formats
     * @param Request $request
     * @param int $page
     * @param string $_forma
     * @return Response
     */
    public function index(
        Request $request,
        int $page,
        string $_format
    ): Response {

        $tag = null;

        $em = $this->getDoctrine()->getManager();

        $tags = $em->getRepository(Tag::class);

        if ($request->query->has('tag')) {

            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        }

        $posts = $em->getRepository(Post::class);
        $latestPosts = $posts->findLatest($page, $tag);

        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templating.html#template-suffix
        return $this->render('blog/index.' . $_format . '.twig', [
            'paginator' => $latestPosts,
        ]);
    }

    /**
     * @Route("/posts/{slug}", methods={"GET"}, name="blog_post")
     * NOTE: The $post controller argument is automatically injected by Symfony
     * after performing a database query looking for a Post with the 'slug'
     * value given in the route.
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
     * @return Response
     */
    public function postShow(Request $request, $slug, Post $post): Response
    {
        $slug = $slug;
    
        // Get the requested  page
        $post = $this->getDoctrine()->GetRepository(Post::class)->findOneBy(["slug" => $slug]);

        //Get data from the input of the comment answer
        $getCommentAnswer = $request->query->get('commentAnswer');

        //Get the comment id
        $commentId = $request->query->get('commentId');   

        if($user = $this->getUser())
        {
            if(!empty($getCommentAnswer))
            {
                $comment = new Comment();
                $comment->setAuthor($user);
                $comment->setContent($getCommentAnswer);
                $comment->setParentId($commentId);
                $comment->setPost($post);


                $em = $this->getDoctrine()->getManager();
                $em->persist($comment);
                $em->flush();
                return $this->redirectToRoute('blog_index');

            }     
        }
        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }

    /**
     * @Route("/comment/{postSlug}/new", methods={"POST"}, name="comment_new")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("post", options={"mapping": {"postSlug": "slug"}})
     *
     * NOTE: The ParamConverter mapping is required because the route parameter
     * (postSlug) doesn't match any of the Doctrine entity properties (slug).
     * See
     * https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#doctrine-converter
     * @param Request $request
     * @param Post $post
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function commentNew(Request $request, Post $post, EventDispatcherInterface $eventDispatcher): Response
    {
        $comment = new Comment();
        /** @var User $user */
        $user = $this->getUser();
        $comment->setAuthor($user);
        $post->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            // When an event is dispatched, Symfony notifies it to all the listeners
            // and subscribers registered to it. Listeners can modify the information
            // passed in the event and they can even modify the execution flow, so
            // there's no guarantee that the rest of this controller will be executed.
            // See https://symfony.com/doc/current/components/event_dispatcher.html
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

            return $this->redirectToRoute('blog_post', ['slug' => $post->getSlug()]);
        }

        return $this->render('blog/comment_form_error.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * This controller is called directly via the render() function in the
     * blog/post_show.html.twig template. That's why it's not needed to define
     * a route name for it.
     *
     * The "id" of the Post is passed in and then turned into a Post object
     * automatically by the ParamConverter.
     * @param Post $post
     * @return Response
     */
    public function commentForm(Post $post): Response
    {
        $form = $this->createForm(CommentType::class);

        return $this->render('blog/_comment_form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="blog_search")
     * @param Request $request
     * @param PostRepository $posts
     * @return Response
     */
    public function search(Request $request, PostRepository $posts): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('blog/search.html.twig');
        }

        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);
        $foundPosts = $posts->findBySearchQuery($query, $limit);

        $results = [];
        foreach ($foundPosts as $post) {
            $results[] = [
                'title' => htmlspecialchars($post->getTitle() ?: '', ENT_COMPAT | ENT_HTML5),
                'date' => $post->getPublishedAt()->format('M d, Y'),
                'author' => htmlspecialchars(
                    $post->getAuthor() !== null ? ($post->getAuthor()->getFullName() ?? '') : '',
                    ENT_COMPAT | ENT_HTML5
                ),
                'summary' => htmlspecialchars($post->getSummary() ?: '', ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('blog_post', ['slug' => $post->getSlug()]),
            ];
        }

        return $this->json($results);
    }
}
