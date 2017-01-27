<?php

namespace Tests\Cases;

use MoySklad\Components\Expand;
use MoySklad\Components\Http\RequestLog;
use MoySklad\Components\Specs\QuerySpecs;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Assortment;
use MoySklad\Entities\Employee;
use MoySklad\Entities\Group;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Products\Service;
use MoySklad\Lists\EntityList;

require_once "TestCase.php";

class EntityGetTest extends TestCase{

    public function setUp()
    {
        parent::setUp();
    }

    public function testGetProductList(){
        $this->methodStart();
        $this->say("Start getting products");
        $this->timeStart();
        $productList = Product::listQuery($this->sklad, QuerySpecs::create([
            'maxResults' => 25
        ]))->get();
       // dd($productList->meta);
        $this->say("Took " . $this->timeEnd() . " sec");
        $this->assertTrue(
            $productList[0] instanceof Product
        );

        $this->say("Start getting assortment");
        $this->timeStart();
        $assortmentList = Assortment::listQuery($this->sklad, QuerySpecs::create([
            'maxResults' => 25
        ]))->get();
        $this->say("Took " . $this->timeEnd() . " sec");
        $this->say("Start transform, have " . $assortmentList->count() . " items\n");
        $this->timeStart();
        $assortmentList->transformItemsToMetaClass()
            ->each(function(AbstractEntity $e){
                $this->assertTrue(
                    $e instanceof Product ||
                    $e instanceof Service
                );
            });
        echo "Took " . $this->timeEnd() . " sec.";
        $this->methodEnd();
        return $productList;
    }

    /**
     * @depends testGetProductList
     */
    public function testProductRelations(EntityList $productList){
        $this->methodStart();
        $product = $productList[0];
        $this->assertTrue(
            $product->relations->group instanceof Group
        );
        $this->methodEnd();
    }

    /**
     * @depends testGetProductList
     */
    public function testEntityRefresh(EntityList $productList){
        $this->methodStart();
        /**
         * @var Product $product
         */
        $product = $productList[0];
        $this->assertEquals(
            $product->id,
            $product->fresh()->id
        );
        $this->methodEnd();
    }

    public function testGetProductListWithExpand(){
        $this->methodStart();
        $products = Product::listQuery($this->sklad, QuerySpecs::create([
            'maxResults' => 5
        ]))->withExpand(Expand::create(['owner']))->get()->each(function(Product $p){
           $this->assertNotNull(
               $p->relations->find(Employee::class)->id
           );
        });
        $this->methodEnd();
    }

    public function testGetProductWithExpand(){
        $this->methodStart();
        $someProduct = Product::listQuery($this->sklad, QuerySpecs::create(['maxResults' => 1]))->get()->get(0);
        $sameProduct = Product::byId($this->sklad, $someProduct->id, Expand::create(['owner']));
        $this->assertNotNull(
            $sameProduct->relations->find(Employee::class)->id
        );
        $this->methodEnd();
    }
}