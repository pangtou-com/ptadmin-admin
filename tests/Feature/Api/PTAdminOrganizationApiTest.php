<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Api;

use PTAdmin\Admin\Models\AdminDepartment;
use PTAdmin\Admin\Models\AdminOrganization;
use PTAdmin\Admin\Models\AdminTenant;
use PTAdmin\Admin\Models\AdminUserOrganizationRelation;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminOrganizationApiTest extends TestCase
{
    public function test_tenant_organization_and_department_endpoints_can_create_update_and_list_records(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $token = $this->issueAdminToken($this->createAdminSystem([
            'username' => 'founder_org',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]));

        $tenantResponse = $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/tenants', [
            'code' => 'tenant_a',
            'name' => '租户A',
            'status' => 1,
            'settings_json' => ['theme' => 'light'],
        ]);

        $tenantResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'code' => 'tenant_a',
                'name' => '租户A',
                'status' => 1,
                'settings_json' => ['theme' => 'light'],
            ],
        ]);

        /** @var AdminTenant $tenant */
        $tenant = AdminTenant::query()->where('code', 'tenant_a')->firstOrFail();

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/tenants/'.$tenant->id, [
            'name' => '租户A-已更新',
            'status' => 0,
        ])->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $tenant->id,
                'name' => '租户A-已更新',
                'status' => 0,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/tenants')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $organizationResponse = $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/organizations', [
            'tenant_id' => $tenant->id,
            'code' => 'org_a',
            'name' => '组织A',
            'sort' => 10,
            'meta_json' => ['level' => 'L1'],
        ]);

        $organizationResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'tenant_id' => $tenant->id,
                'code' => 'org_a',
                'name' => '组织A',
                'sort' => 10,
                'status' => 1,
            ],
        ]);

        /** @var AdminOrganization $organization */
        $organization = AdminOrganization::query()->where('code', 'org_a')->firstOrFail();

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/organizations/'.$organization->id, [
            'name' => '组织A-已更新',
            'sort' => 20,
        ])->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $organization->id,
                'name' => '组织A-已更新',
                'sort' => 20,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/organizations?tenant_id='.$tenant->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $departmentResponse = $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/departments', [
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'code' => 'dept_a',
            'name' => '部门A',
            'sort' => 5,
        ]);

        $departmentResponse->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'tenant_id' => $tenant->id,
                'organization_id' => $organization->id,
                'code' => 'dept_a',
                'name' => '部门A',
                'sort' => 5,
                'status' => 1,
            ],
        ]);

        /** @var AdminDepartment $department */
        $department = AdminDepartment::query()->where('code', 'dept_a')->firstOrFail();

        $this->withHeaders($this->jsonApiHeaders($token))->putJson('/system/departments/'.$department->id, [
            'name' => '部门A-已更新',
            'status' => 0,
        ])->assertOk()->assertJson([
            'code' => 0,
            'data' => [
                'id' => $department->id,
                'name' => '部门A-已更新',
                'status' => 0,
            ],
        ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/departments?organization_id='.$organization->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_relation_endpoints_can_sync_and_switch_primary_relation(): void
    {
        $this->createSystemsTable();
        $this->createUserTokensTable();
        $this->migratePackageTables();

        $founder = $this->createAdminSystem([
            'username' => 'founder_relation',
            'nickname' => 'Founder',
            'is_founder' => 1,
        ]);
        $member = $this->createAdminSystem([
            'username' => 'member_relation',
            'nickname' => 'Member',
        ]);
        $token = $this->issueAdminToken($founder);

        $tenant = AdminTenant::query()->create([
            'code' => 'tenant_rel',
            'name' => '租户Rel',
            'status' => 1,
        ]);

        $organizationA = AdminOrganization::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => 0,
            'code' => 'org_rel_a',
            'name' => '组织A',
            'status' => 1,
            'sort' => 0,
        ]);
        $organizationB = AdminOrganization::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => 0,
            'code' => 'org_rel_b',
            'name' => '组织B',
            'status' => 1,
            'sort' => 1,
        ]);
        $departmentA = AdminDepartment::query()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organizationA->id,
            'parent_id' => 0,
            'code' => 'dept_rel_a',
            'name' => '部门A',
            'status' => 1,
            'sort' => 0,
        ]);
        $departmentB = AdminDepartment::query()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organizationB->id,
            'parent_id' => 0,
            'code' => 'dept_rel_b',
            'name' => '部门B',
            'status' => 1,
            'sort' => 1,
        ]);

        $syncResponse = $this->withHeaders($this->jsonApiHeaders($token))->postJson('/system/systems-org/'.$member->id, [
            'tenant_id' => $tenant->id,
            'relations' => [
                [
                    'organization_id' => $organizationA->id,
                    'department_id' => $departmentA->id,
                    'is_primary' => 1,
                ],
                [
                    'organization_id' => $organizationB->id,
                    'department_id' => $departmentB->id,
                    'is_primary' => 0,
                ],
            ],
        ]);

        $syncResponse->assertOk()->assertJson([
            'code' => 0,
        ]);

        self::assertCount(2, (array) $syncResponse->json('data'));
        self::assertDatabaseHas('admin_user_org_relations', [
            'user_id' => $member->id,
            'organization_id' => $organizationA->id,
            'department_id' => $departmentA->id,
            'is_primary' => 1,
        ]);

        $secondaryRelation = AdminUserOrganizationRelation::query()
            ->where('user_id', $member->id)
            ->where('organization_id', $organizationB->id)
            ->firstOrFail()
        ;

        $this->withHeaders($this->jsonApiHeaders($token))
            ->putJson('/system/systems-org-primary/'.$secondaryRelation->id)
            ->assertOk()
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $secondaryRelation->id,
                    'is_primary' => 1,
                ],
            ]);

        $this->withHeaders($this->jsonApiHeaders($token))
            ->getJson('/system/systems-org/'.$member->id.'?tenant_id='.$tenant->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        self::assertDatabaseHas('admin_user_org_relations', [
            'id' => $secondaryRelation->id,
            'is_primary' => 1,
        ]);
        self::assertSame(
            1,
            AdminUserOrganizationRelation::query()
                ->where('user_id', $member->id)
                ->where('tenant_id', $tenant->id)
                ->where('is_primary', 1)
                ->count()
        );
    }
}
