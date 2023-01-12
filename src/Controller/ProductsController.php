<?php

namespace App\Controller;

use App\DTO\LowestPriceEnquiry;
use App\Entity\Promotion;
use App\Filter\PromotionsFilterInterface;
use App\Repository\ProductRepository;
use App\Service\Serializer\DTOSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductsController extends AbstractController
{
    public function __construct(
        private ProductRepository      $repository,
        private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/products/{id}/lowest-price', name: 'lowest_price', methods: 'POST')]
    public function lowestPrice(
        Request                   $request,
        int                       $id,
        DTOSerializer             $serializer,
        PromotionsFilterInterface $promotionsFilter): Response
    {
        if ($request->headers->has('force_fail')) {
            return new JsonResponse(
                ['error' => 'Promotions engine failure message'],
                $request->headers->get('force_fail'));
        }

        // 1) deserialize json data in EnquiryDTO

        $lowestPriceEnquiry = $serializer->deserialize(
            $request->getContent(), LowestPriceEnquiry::class, 'json'
        );

        $product = $this->repository->find($id); // add error handling for not found
        $lowestPriceEnquiry->setProduct($product);

        $promotions = $this->entityManager->getRepository(Promotion::class)->findValidForProduct(
            $product,
            date_create_immutable($lowestPriceEnquiry->getRequestDate())
        );

        // 2) pass the enquiry into a promotions filter
        // the appropriate promotion.sql will be applied
        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry, ...$promotions);

        $responseContent = $serializer->serialize($modifiedEnquiry, 'json');

        return new Response($responseContent, 200, ['Content-Type' => 'application/json']);

    }


    #[Route('/products/{id}/promotions', name: 'promotions', methods: 'GET')]
    public function promotions()
    {
//
    }
}