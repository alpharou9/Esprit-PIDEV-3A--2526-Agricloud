<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/events')]
#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    // ── Public event listing ──────────────────────────────────────
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $repo, PaginatorInterface $paginator): Response
    {
        $q        = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $status   = $request->query->get('status', '');

        $pagination = $paginator->paginate(
            $repo->publicQueryBuilder($q ?: null, $category ?: null, $status ?: null),
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('event/index.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'category'   => $category,
            'status'     => $status,
        ]);
    }

    // ── My events (organizer / admin) ─────────────────────────────
    #[Route('/my-events', name: 'event_my', methods: ['GET'])]
    public function myEvents(Request $request, EventRepository $repo, PaginatorInterface $paginator): Response
    {
        $q  = $request->query->get('q', '');
        $qb = $this->isGranted('ROLE_ADMIN')
            ? $repo->adminQueryBuilder($q ?: null, $request->query->get('status') ?: null)
            : $repo->organizerQueryBuilder($this->getUser(), $q ?: null);

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        return $this->render('event/my_events.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'status'     => $request->query->get('status', ''),
        ]);
    }

    // ── My registrations ─────────────────────────────────────────
    #[Route('/my-registrations', name: 'event_my_registrations', methods: ['GET'])]
    public function myRegistrations(ParticipationRepository $repo): Response
    {
        $participations = $repo->findByUser($this->getUser());

        return $this->render('event/my_registrations.html.twig', [
            'participations' => $participations,
        ]);
    }

    // ── New event ─────────────────────────────────────────────────
    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setUser($this->getUser());
            $event->setCreatedAt(new \DateTime());
            $event->setSlug(strtolower((string) $slugger->slug($event->getTitle())) . '-' . uniqid());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe    = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('events_upload_dir'), $newName);
                $event->setImage($newName);
            }

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created.');
            return $this->redirectToRoute('event_my');
        }

        return $this->render('event/event_form.html.twig', ['form' => $form, 'event' => null]);
    }

    // ── Edit event ────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setUpdatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe    = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('events_upload_dir'), $newName);
                $event->setImage($newName);
            }

            $em->flush();
            $this->addFlash('success', 'Event updated.');
            return $this->redirectToRoute('event_my');
        }

        return $this->render('event/event_form.html.twig', ['form' => $form, 'event' => $event]);
    }

    // ── Delete event ──────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('event_my');
        }

        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Event deleted.');
        return $this->redirectToRoute('event_my');
    }

    // ── Register for event ────────────────────────────────────────
    #[Route('/{id}/register', name: 'event_register', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function register(Event $event, Request $request, EntityManagerInterface $em, ParticipationRepository $repo, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('register_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if (!$event->isRegistrationOpen()) {
            $this->addFlash('error', 'Registration is closed for this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        if ($event->isFull()) {
            $this->addFlash('error', 'This event is full.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $existing = $repo->findOneBy(['event' => $event, 'user' => $this->getUser()]);
        $now = new \DateTime();
        if ($existing) {
            if ($existing->getStatus() === 'cancelled') {
                $existing->setStatus('confirmed');
                $existing->setRegistrationDate($now);
                $existing->setCancelledAt(null);
                $existing->setCancelledReason(null);
                $existing->setUpdatedAt($now);
                $em->flush();
                $this->sendTicketEmail($mailer, $existing);
                $this->addFlash('success', 'You are registered for this event! Check your email for your ticket.');
                return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
            }
            $this->addFlash('error', 'You are already registered for this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $participation = new Participation();
        $participation->setEvent($event);
        $participation->setUser($this->getUser());
        $participation->setStatus('confirmed');
        $participation->setRegistrationDate($now);
        $participation->setCreatedAt($now);
        $em->persist($participation);
        $em->flush();

        $this->sendTicketEmail($mailer, $participation);
        $this->addFlash('success', 'You are registered for this event! Check your email for your ticket.');
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }

    // ── Cancel registration ───────────────────────────────────────
    #[Route('/participation/{id}/cancel', name: 'event_cancel_registration', methods: ['POST'])]
    public function cancelRegistration(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        if ($participation->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cancel_participation_' . $participation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('event_my_registrations');
        }

        $participation->setStatus('cancelled');
        $participation->setCancelledAt(new \DateTime());
        $participation->setCancelledReason('User cancelled');
        $em->flush();

        $this->addFlash('success', 'Registration cancelled.');
        return $this->redirectToRoute('event_my_registrations');
    }

    // ── Admin: manage participants ─────────────────────────────────
    #[Route('/{id}/participants', name: 'event_participants', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function participants(Event $event): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $event->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('event/participants.html.twig', ['event' => $event]);
    }

    // ── Admin: mark attended ──────────────────────────────────────
    #[Route('/participation/{id}/attended', name: 'event_mark_attended', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markAttended(Participation $participation, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('attended_' . $participation->getId(), $request->request->get('_token'))) {
            $participation->setAttended(!$participation->isAttended());
            $participation->setStatus($participation->isAttended() ? 'attended' : 'confirmed');
            $em->flush();
        }
        return $this->redirectToRoute('event_participants', ['id' => $participation->getEvent()->getId()]);
    }

    // ── Calendar JSON feed ────────────────────────────────────────
    #[Route('/calendar/feed', name: 'event_calendar_feed', methods: ['GET'])]
    public function calendarFeed(EventRepository $repo): Response
    {
        $events = $repo->createQueryBuilder('e')
            ->where('e.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();

        $colors = [
            'upcoming'  => '#77b81e',
            'ongoing'   => '#3b82f6',
            'completed' => '#6b7280',
        ];

        $data = array_map(function (Event $e) use ($colors): array {
            return [
                'id'              => $e->getId(),
                'title'           => $e->getTitle(),
                'start'           => $e->getEventDate()?->format('c'),
                'end'             => $e->getEndDate()?->format('c'),
                'url'             => $this->generateUrl('event_show', ['id' => $e->getId()]),
                'backgroundColor' => $colors[$e->getStatus()] ?? '#77b81e',
                'borderColor'     => $colors[$e->getStatus()] ?? '#77b81e',
                'extendedProps'   => [
                    'location' => $e->getLocation(),
                    'category' => $e->getCategory(),
                    'status'   => $e->getStatus(),
                ],
            ];
        }, $events);

        return $this->json($data);
    }

    // ── iCal download ─────────────────────────────────────────────
    #[Route('/{id}/ical', name: 'event_ical', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function ical(Event $event): Response
    {
        $fmt = fn(\DateTimeInterface $dt) => $dt->format('Ymd\THis\Z');
        $now = $fmt(new \DateTime('UTC'));
        $dtstart = $event->getEventDate() ? $fmt(\DateTime::createFromInterface($event->getEventDate())->setTimezone(new \DateTimeZone('UTC'))) : $now;
        $dtend   = $event->getEndDate()   ? $fmt(\DateTime::createFromInterface($event->getEndDate())->setTimezone(new \DateTimeZone('UTC'))) : $dtstart;
        $summary = addcslashes($event->getTitle(), ',;\\');
        $location = addcslashes($event->getLocation(), ',;\\');
        $desc    = addcslashes(substr(strip_tags($event->getDescription()), 0, 500), ',;\\');
        $uid     = 'event-' . $event->getId() . '@agricloud.tn';

        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//AgriCloud//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n"
            . "BEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:{$now}\r\nDTSTART:{$dtstart}\r\nDTEND:{$dtend}\r\n"
            . "SUMMARY:{$summary}\r\nLOCATION:{$location}\r\nDESCRIPTION:{$desc}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        return new Response($ical, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event-' . $event->getId() . '.ics"',
        ]);
    }

    // ── Send ticket email ─────────────────────────────────────────
    private function sendTicketEmail(MailerInterface $mailer, Participation $participation): void
    {
        try {
            $user  = $participation->getUser();
            $event = $participation->getEvent();
            $html  = $this->renderView('emails/event_ticket.html.twig', [
                'participation' => $participation,
                'event'         => $event,
                'user'          => $user,
            ]);
            $email = (new Email())
                ->from('noreply@agricloud.tn')
                ->to($user->getEmail())
                ->subject('Your ticket for: ' . $event->getTitle())
                ->html($html);
            $mailer->send($email);
        } catch (\Throwable) {
            // Mailer not configured — registration still succeeds
        }
    }

    // ── Show event (must be last) ─────────────────────────────────
    #[Route('/{id}', name: 'event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event, ParticipationRepository $repo): Response
    {
        $myParticipation = $repo->findOneBy(['event' => $event, 'user' => $this->getUser()]);

        return $this->render('event/show.html.twig', [
            'event'           => $event,
            'myParticipation' => $myParticipation,
        ]);
    }
}
