<?php

namespace LaravelEnso\Tables\Tests\units\Services\Table\Builders;

use Tests\TestCase;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Tables\Tests\units\Services\SetUp;
use LaravelEnso\Tables\Tests\units\Services\TestModel;
use LaravelEnso\Tables\app\Services\Data\Builders\Data;
use LaravelEnso\Tables\Tests\units\Services\BuilderTestEnum;

class DataTest extends TestCase
{
    use SetUp;

    /** @test */
    public function can_get_data()
    {
        $response = $this->requestResponse();

        $this->assertCount(TestModel::count(), $response);

        $this->assertTrue(
            $response->first()
                ->diff($this->testModel->toArray())
                ->isEmpty()
        );
    }

    /** @test */
    public function can_get_data_with_appends()
    {
        $this->config->get('appends')->push('custom');

        $response = $this->requestResponse();

        $this->assertEquals(
            'name',
            $response->first()
                ->get('custom')
                ->get('relation')
        );
    }

    /** @test */
    public function can_get_data_with_flatten()
    {
        $this->config->get('appends')->push('custom');
        
        $this->config->put('flatten', true);

        $response = $this->requestResponse();

        $this->assertEquals(
            'name',
            $response->first()->get('custom.relation')
        );
    }

    /** @test */
    public function can_get_data_with_enum()
    {
        $this->testModel->update(['color' => BuilderTestEnum::Blue]);

        $this->config->meta()->set('enum', true);

        $this->config->columns()->push(new Obj([
            'name' => 'color',
            'data' => 'color',
            'enum' => BuilderTestEnum::class,
            'meta' => []
        ]));

        $response = $this->requestResponse();

        $this->assertEquals(
            BuilderTestEnum::get($this->testModel->color),
            $response->first()->get('color')
        );
    }

    /** @test */
    public function can_get_data_with_date()
    {
        $this->config->meta()->set('date', true);

        $this->config->columns()->push(new Obj([
            'name' => 'created_at',
            'dateFormat' => 'Y-m-d',
            'meta' => ['date' => true],
        ]));

        $response = $this->requestResponse();

        $this->assertEquals(
            $this->testModel->created_at->format('Y-m-d'),
            $response->first()->get('created_at')
        );
    }

    /** @test */
    public function can_get_data_with_cents()
    {
        $this->config->meta()->set('cents', true);

        $this->config->columns()->push(new Obj([
            'name' => 'price',
            'data' => 'price',
            'meta' => ['cents' => true]
        ]));

        $response = $this->requestResponse();

        $this->assertEquals(
            $this->testModel->price / 100,
            $response->first()->get('price')
        );
    }

    /** @test */
    public function can_get_data_with_sort()
    {
        $this->createTestModel();

        $column = new Obj([
            'name' => 'id',
            'data' => 'id',
            'meta' => ['sortable' => true, 'sort' => 'DESC'],
        ]);

        $this->config->columns()->push($column);

        $this->config->meta()->put('sort', true);

        $response = $this->requestResponse();

        $this->assertEquals(
            TestModel::orderByDesc('id')->first()->id,
            $response->first()->get('id')
        );
    }

    /** @test */
    public function can_get_data_with_sort_null_last()
    {
        $secondModel = $this->createTestModel();

        $this->testModel->update(['name' => null]);

        $column = new Obj([
            'name' => 'name',
            'data' => 'name',
            'meta' => ['sortable' => true, 'sort' => 'ASC', 'nullLast' => true],
        ]);

        $this->config->columns()->push($column);

        $this->config->meta()->put('sort', true);

        $response = $this->requestResponse();

        $this->assertEquals(
            $secondModel->name,
            $response->first()->get('name')
        );
    }

    /** @test */
    public function can_get_data_with_limit()
    {
        $this->config->meta()->set('length', 0);

        $response = $this->requestResponse();

        $this->assertCount(0, $response);
    }

    /** @test */
    public function can_use_full_info_record_limit()
    {
        $limit = 1;

        $this->createTestModel();

        $this->config->columns()->push(new Obj([
            'name' => 'name',
            'data' => 'name',
            'meta' => ['searchable' => true]
        ]));

        $this->config->set('comparisonOperator', 'LIKE');

        $this->config->meta()->set('search', $this->testModel->name)
            ->set('fullInfoRecordLimit', $limit);
            
        $response = $this->requestResponse();

        $this->assertCount($limit, $response);
    }

    private function requestResponse()
    {
        $builder = new Data($this->table, $this->config);

        return new Obj($builder->data());
    }
}