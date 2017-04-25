<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Project;
use AppBundle\Form\ProjectType;
use AppBundle\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints\DateTime;


/**
 * Project controller.
 *
 * @Route("project")
 */
class ProjectController extends Controller
{
    /**
     * Lists all project entities.
     *
     * @Route("/", name="project_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $projects = $em->getRepository('AppBundle\Entity\Project')->findAll();

        return $this->render('project/index.html.twig', array(
            'projects' => $projects,
            'user' => $this->getUser(),
            'countries' => Intl::getRegionBundle()->getCountryNames(),
        ));
    }

    /**
     * Creates a new project entity.
     *
     * @Route("/new", name="project_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $project = new Project();
        $form = $this->createForm('AppBundle\Form\ProjectType', $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**@var UploadedFile $file */
            $file = $project->getImageForm();

            $filename = md5($project->getTitle().''.$project->getDateCreated()) . $file->getFileInfo()->getExtension();
            $file->move(
                $this->get('kernel')->getRootDir() . '/../web/images/project/',
                $filename);

            $project->setImage($filename);

            $project->setDateCreated(new \DateTime());
            $project->setDateUpdated(new \DateTime());
            $project->setUser($this->getUser());
            $em = $this->getDoctrine()->getManager();


            $em->persist($project);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'New project was created.');

            return $this->redirectToRoute('project_show', array('id' => $project->getId()));
        }

        return $this->render('project/new.html.twig', array(
            'project' => $project,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a project entity.
     *
     * @Route("/{id}", name="project_show")
     * @Method("GET")
     * @param Project $project
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Project $project)
    {

        $deleteForm = $this->createDeleteForm($project);

        return $this->render('project/show.html.twig', array(
            'project' => $project,
            'delete_form' => $deleteForm->createView(),
            'countries' => Intl::getRegionBundle()->getCountryNames(),
        ));
    }

    /**
     * Displays a form to edit an existing project entity.
     *
     * @Route("/{id}/edit", name="project_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param Project $project
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Project $project)
    {
        if($project->getUser()->getId() != $this->getUser()->getId() && !$this->isGranted('ROLE_ADMIN', $this->getUser() )) {
            $this->get('session')->getFlashBag()->add('error', 'Only owners can edit their projects.');
            return $this->redirectToRoute('project_index');
        }

        $deleteForm = $this->createDeleteForm($project);
        $editForm = $this->createForm('AppBundle\Form\ProjectType', $project);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $project->setDateUpdated(new \DateTime());

            if($project->getImageForm() instanceof UploadedFile) {

                /**@var UploadedFile $file */
                $file = $project->getImageForm();

                $filename = md5($project->getTitle().''.$project->getDateCreated()->format('Y/m/d H:i:s')) . $file->getFileInfo()->getExtension();
                $file->move(
                    $this->get('kernel')->getRootDir() . '/../web/images/project/',
                    $filename);

                $project->setImage($filename);
            }

            $this->getDoctrine()->getManager()->flush();

            $this->get('session')->getFlashBag()->add('success', 'Project is edited successfully.');

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/edit.html.twig', array(
            'project' => $project,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a project entity.
     *
     * @Route("/manage/projects/{id}/delete", name="admin_manage_project")
     * @Method("GET")
     * @param Request $request
     * @param Project $project
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Project $project)
    {
            $em = $this->getDoctrine()->getManager();
            $em->remove($project);
            $em->flush();

        return $this->redirectToRoute('admin_manage_project');
    }

    /**
     * Creates a form to delete a project entity.
     *
     * @param Project $project The project entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Project $project)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('project_delete', array('id' => $project->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @Route("/manage/projects/", name="admin_manage_projects")
     */
    public function manageProjectsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $projects = $em->getRepository('AppBundle\Entity\Project')->findAll();

        return $this->render('project/manage.html.twig', array(
            'projects' => $projects,
            'user' => $this->getUser(),
            'countries' => Intl::getRegionBundle()->getCountryNames(),
        ));
    }
}
