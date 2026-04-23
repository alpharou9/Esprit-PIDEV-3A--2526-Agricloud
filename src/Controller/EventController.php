<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participation;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Service\EventRecommendationService;
use App\Service\EventTicketContextBuilder;
use App\Service\EventTicketService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/events')]
#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EventTicketContextBuilder $ticketContextBuilder,
        private readonly EventRecommendationService $eventRecommendationService,
    ) {
    }

    #[Route('/api', name: 'event_api_index', methods: ['GET'])]
    public function apiIndex(Request $request, EventRepository $repo): JsonResponse
    {
        $q = trim((string) $request->query->get('q', '')) ?: null;
        $category = trim((string) $request->query->get('category', '')) ?: null;
        $status = trim((string) $request->query->get('status', '')) ?: null;

        $events = $repo->publicQueryBuilder($q, $category, $status)
            ->getQuery()
            ->getResult();

        return $this->json([
            'count' => count($events),
            'items' => array_map(
                fn (Event $event) => $this->serializeEvent($event),
                $events
            ),
        ]);
    }

    #[Route('/api/{id}', name: 'event_api_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiShow(Event $event, ParticipationRepository $repo): JsonResponse
    {
        $myParticipation = $repo->findOneBy([
            'event' => $event,
            'user' => $this->getUser(),
        ]);

        return $this->json([
            'item' => $this->serializeEvent($event, $myParticipation),
        ]);
    }

    #[Route('/api/{id}/register', name: 'event_api_register', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function apiRegister(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        ParticipationRepository $repo,
        EventTicketService $ticketService
    ): JsonResponse {
        $payload = [];
        $content = trim($request->getContent());
        if ($content !== '') {
            $payload = json_decode($content, true);

            if (!is_array($payload)) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Invalid JSON payload.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $csrfToken = (string) ($payload['_token'] ?? $request->headers->get('X-CSRF-TOKEN', ''));
        if (!$this->isCsrfTokenValid('register_event_' . $event->getId(), $csrfToken)) {
            return $this->json([
                'ok' => false,
                'message' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        $result = $this->registerUserToEvent($event, $em, $repo, $ticketService);

        return $this->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'ticketEmailSent' => $result['ticketEmailSent'],
            'ticketMessage' => $result['ticketMessage'],
            'participation' => $result['participation'] ? [
                'id' => $result['participation']->getId(),
                'status' => $result['participation']->getStatus(),
                'registrationDate' => $result['participation']->getRegistrationDate()?->format(DATE_ATOM),
            ] : null,
            'event' => $this->serializeEvent($event, $result['participation']),
        ], $result['status']);
    }

    #[Route('/api/calendar', name: 'event_api_calendar', methods: ['GET'])]
    public function apiCalendar(Request $request, EventRepository $repo): JsonResponse
    {
        $monthStart = $this->resolveCalendarMonth((string) $request->query->get('month', ''));
        $monthEnd = $monthStart->modify('first day of next month');
        $events = $repo->findBetweenDates($monthStart, $monthEnd);

        return $this->json([
            'month' => $monthStart->format('Y-m'),
            'label' => $monthStart->format('F Y'),
            'items' => array_map(
                fn (Event $event) => $this->serializeCalendarEvent($event),
                $events
            ),
        ]);
    }

    #[Route('/calendar', name: 'event_calendar', methods: ['GET'])]
    public function calendar(Request $request, EventRepository $repo): Response
    {
        $monthStart = $this->resolveCalendarMonth((string) $request->query->get('month', ''));
        $monthEnd = $monthStart->modify('last day of this month');
        $gridStart = $monthStart->modify('-' . ((int) $monthStart->format('N') - 1) . ' days');
        $gridEnd = $monthEnd->modify('+' . (7 - (int) $monthEnd->format('N')) . ' days');
        $events = $repo->findBetweenDates($gridStart, $gridEnd->modify('+1 day'));

        return $this->render('event/calendar.html.twig', [
            'calendarWeeks' => $this->buildCalendarWeeks($monthStart, $gridStart, $gridEnd, $events),
            'calendarMonth' => $monthStart,
            'previousMonth' => $monthStart->modify('-1 month')->format('Y-m'),
            'nextMonth' => $monthStart->modify('+1 month')->format('Y-m'),
            'calendarApiUrl' => $this->generateUrl('event_api_calendar', ['month' => $monthStart->format('Y-m')]),
        ]);
    }

    #[Route('/api/recommendations', name: 'event_api_recommendations', methods: ['GET'])]
    public function apiRecommendations(
        Request $request,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): JsonResponse {
        $need = trim((string) $request->query->get('need', ''));
        $location = trim((string) $request->query->get('location', ''));
        $participations = $participationRepository->findByUser($this->getUser());
        $recommendations = $this->eventRecommendationService->recommend(
            $eventRepository->findRecommendationCandidates(),
            $location,
            $need,
            $participations
        );

        return $this->json([
            'count' => count($recommendations),
            'need' => $need,
            'location' => $location,
            'locationProvided' => $location !== '',
            'items' => array_map(
                fn (array $recommendation) => $this->serializeRecommendation($recommendation),
                $recommendations
            ),
        ]);
    }

    #[Route('/recommendations', name: 'event_recommendations', methods: ['GET'])]
    public function recommendations(
        Request $request,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): Response {
        $need = trim((string) $request->query->get('need', ''));
        $location = trim((string) $request->query->get('location', ''));
        $participations = $participationRepository->findByUser($this->getUser());
        $recommendations = $this->eventRecommendationService->recommend(
            $eventRepository->findRecommendationCandidates(),
            $location,
            $need,
            $participations
        );

        return $this->render('event/recommendations.html.twig', [
            'recommendations' => $recommendations,
            'need' => $need,
            'location' => $location,
        ]);
    }
    // ── Public event listing ──────────────────────────────────────
    #[Route('', name: 'event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $repo, PaginatorInterface $paginator): Response
    {
        $q        = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $status   = $request->query->get('status', '');

        $pagination = $paginator->paginate(
            $repo->publicQueryBuilder($q ?: null, $category ?: null, $status ?: null),
            $request->query->getInt('page', 1), 9
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
    public function register(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        ParticipationRepository $repo,
        EventTicketService $ticketService
    ): Response
    {
        if (!$this->isCsrfTokenValid('register_event_' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $result = $this->registerUserToEvent($event, $em, $repo, $ticketService);

        if (!$result['ok']) {
            $this->addFlash('error', $result['message']);
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $this->addFlash('success', 'You are registered for this event!');
        if (!$result['ticketEmailSent'] && $result['ticketMessage'] !== null) {
            $this->addFlash('warning', $result['ticketMessage']);
        }

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

    private function registerUserToEvent(
        Event $event,
        EntityManagerInterface $em,
        ParticipationRepository $repo,
        EventTicketService $ticketService
    ): array {
        if (!$event->isRegistrationOpen()) {
            return [
                'ok' => false,
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Registration is closed for this event.',
                'ticketEmailSent' => false,
                'ticketMessage' => null,
                'participation' => null,
            ];
        }

        if ($event->isFull()) {
            return [
                'ok' => false,
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'This event is full.',
                'ticketEmailSent' => false,
                'ticketMessage' => null,
                'participation' => null,
            ];
        }

        $user = $this->getUser();
        if ($user === null) {
            return [
                'ok' => false,
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'You must be logged in to register.',
                'ticketEmailSent' => false,
                'ticketMessage' => null,
                'participation' => null,
            ];
        }

        $existing = $repo->findOneBy(['event' => $event, 'user' => $user]);
        $now = new \DateTime();

        if ($existing instanceof Participation) {
            if ($existing->getStatus() !== 'cancelled') {
                return [
                    'ok' => false,
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'You are already registered for this event.',
                    'ticketEmailSent' => false,
                    'ticketMessage' => null,
                    'participation' => $existing,
                ];
            }

            $existing->setStatus('confirmed');
            $existing->setRegistrationDate($now);
            $existing->setCancelledAt(null);
            $existing->setCancelledReason(null);
            $existing->setUpdatedAt($now);
            $em->flush();

            $ticketResult = $ticketService->sendRegistrationTicket($existing);

            return [
                'ok' => true,
                'status' => Response::HTTP_OK,
                'message' => $ticketResult['sent']
                    ? 'You are registered for this event and your ticket was sent by email.'
                    : 'You are registered for this event, but the ticket email could not be sent.',
                'ticketEmailSent' => $ticketResult['sent'],
                'ticketMessage' => $ticketResult['message'],
                'participation' => $existing,
            ];
        }

        $participation = new Participation();
        $participation->setEvent($event);
        $participation->setUser($user);
        $participation->setStatus('confirmed');
        $participation->setRegistrationDate($now);
        $participation->setCreatedAt($now);
        $em->persist($participation);
        $em->flush();

        $ticketResult = $ticketService->sendRegistrationTicket($participation);

        return [
            'ok' => true,
            'status' => Response::HTTP_CREATED,
            'message' => $ticketResult['sent']
                ? 'You are registered for this event and your ticket was sent by email.'
                : 'You are registered for this event, but the ticket email could not be sent.',
            'ticketEmailSent' => $ticketResult['sent'],
            'ticketMessage' => $ticketResult['message'],
            'participation' => $participation,
        ];
    }

    private function serializeEvent(Event $event, ?Participation $myParticipation = null): array
    {
        $availability = $this->ticketContextBuilder->buildAvailability($event);

        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'slug' => $event->getSlug(),
            'description' => $event->getDescription(),
            'location' => $event->getLocation(),
            'latitude' => $event->getLatitude(),
            'longitude' => $event->getLongitude(),
            'capacity' => $event->getCapacity(),
            'category' => $event->getCategory(),
            'status' => $event->getStatus(),
            'image' => $event->getImage() ? '/uploads/events/' . $event->getImage() : null,
            'eventDate' => $event->getEventDate()?->format(DATE_ATOM),
            'endDate' => $event->getEndDate()?->format(DATE_ATOM),
            'registrationDeadline' => $event->getRegistrationDeadline()?->format(DATE_ATOM),
            'createdAt' => $event->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $event->getUpdatedAt()?->format(DATE_ATOM),
            'confirmedCount' => $event->getConfirmedCount(),
            'isFull' => $event->isFull(),
            'isRegistrationOpen' => $event->isRegistrationOpen(),
            'mapSearchUrl' => $this->ticketContextBuilder->buildMapSearchUrl($event),
            'qrCodeUrl' => $this->ticketContextBuilder->buildQrCodeUrl($event),
            'availability' => $availability,
            'organizer' => [
                'id' => $event->getUser()?->getId(),
                'name' => $event->getUser()?->getName(),
            ],
            'myParticipation' => $myParticipation ? [
                'id' => $myParticipation->getId(),
                'status' => $myParticipation->getStatus(),
                'registrationDate' => $myParticipation->getRegistrationDate()?->format(DATE_ATOM),
                'attended' => $myParticipation->isAttended(),
            ] : null,
        ];
    }

    private function resolveCalendarMonth(string $month): \DateTimeImmutable
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m', $month);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed->setTime(0, 0);
            }
        }

        return (new \DateTimeImmutable('first day of this month'))->setTime(0, 0);
    }

    /**
     * @param Event[] $events
     */
    private function buildCalendarWeeks(
        \DateTimeImmutable $monthStart,
        \DateTimeImmutable $gridStart,
        \DateTimeImmutable $gridEnd,
        array $events
    ): array {
        $eventsByDay = [];
        foreach ($events as $event) {
            $eventDate = $event->getEventDate();
            if ($eventDate === null) {
                continue;
            }

            $startDate = \DateTimeImmutable::createFromInterface($eventDate)->setTime(0, 0);
            $endSource = $event->getEndDate() ?? $eventDate;
            $endDate = \DateTimeImmutable::createFromInterface($endSource)->setTime(0, 0);

            if ($endDate < $startDate) {
                $endDate = $startDate;
            }

            for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
                $key = $date->format('Y-m-d');
                $eventsByDay[$key][] = $event;
            }
        }

        $weeks = [];
        $currentWeek = [];
        for ($date = $gridStart; $date <= $gridEnd; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $currentWeek[] = [
                'date' => $date,
                'isCurrentMonth' => $date->format('Y-m') === $monthStart->format('Y-m'),
                'events' => $eventsByDay[$key] ?? [],
            ];

            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        return $weeks;
    }

    private function serializeCalendarEvent(Event $event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'date' => $event->getEventDate()?->format('Y-m-d'),
            'start' => $event->getEventDate()?->format(DATE_ATOM),
            'end' => $event->getEndDate()?->format(DATE_ATOM),
            'location' => $event->getLocation(),
            'status' => $event->getStatus(),
            'url' => $this->generateUrl('event_show', ['id' => $event->getId()]),
            'availability' => $this->ticketContextBuilder->buildAvailability($event),
        ];
    }

    /**
     * @param array<string, mixed> $recommendation
     */
    private function serializeRecommendation(array $recommendation): array
    {
        /** @var Event $event */
        $event = $recommendation['event'];

        return [
            'score' => $recommendation['score'],
            'locationConfidence' => $recommendation['locationConfidence'],
            'reasons' => $recommendation['reasons'],
            'event' => $this->serializeEvent($event),
        ];
    }
}
