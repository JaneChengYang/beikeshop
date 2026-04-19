@extends('admin::layouts.master')

@section('title', __('admin/common.coupon'))

@section('content')
  <div id="coupon-app" class="card h-min-600" v-cloak>
    <div class="card-body">
      <div class="d-flex justify-content-between mb-4">
        <button type="button" class="btn btn-primary" @click="openCreate">{{ __('common.add') }}</button>
      </div>

      <div class="table-push">
        <table class="table">
          <thead>
            <tr>
              <th>{{ __('common.id') }}</th>
              <th>{{ __('coupon.code') }}</th>
              <th>{{ __('coupon.type') }}</th>
              <th>{{ __('coupon.value') }}</th>
              <th>{{ __('coupon.min_order') }}</th>
              <th>{{ __('coupon.usage') }}</th>
              <th>{{ __('coupon.expires_at') }}</th>
              <th>{{ __('common.status') }}</th>
              <th>{{ __('common.action') }}</th>
            </tr>
          </thead>
          <tbody v-if="coupons.data && coupons.data.length">
            <tr v-for="(item, index) in coupons.data" :key="item.id">
              <td>@{{ item.id }}</td>
              <td><strong>@{{ item.code }}</strong></td>
              <td><span v-if="item.type === 'fixed'">{{ __('coupon.type_fixed') }}</span><span v-else>{{ __('coupon.type_percent') }}</span></td>
              <td>@{{ item.type === 'fixed' ? '$' + item.value : item.value + '%' }}</td>
              <td><span v-if="item.min_order > 0">$@{{ item.min_order }}</span><span v-else>{{ __('coupon.no_limit') }}</span></td>
              <td>@{{ item.used_count }} / <span v-if="item.usage_limit > 0">@{{ item.usage_limit }}</span><span v-else>{{ __('coupon.no_limit') }}</span></td>
              <td><span v-if="item.expires_at">@{{ item.expires_at.substring(0, 10) }}</span><span v-else>{{ __('coupon.no_limit') }}</span></td>
              <td>
                <span class="text-success" v-if="item.active">{{ __('common.enabled') }}</span>
                <span class="text-secondary" v-else>{{ __('common.disabled') }}</span>
              </td>
              <td>
                <button class="btn btn-outline-secondary btn-sm" @click="openEdit(index)">{{ __('common.edit') }}</button>
                <button class="btn btn-outline-danger btn-sm ms-1" @click="deleteItem(item.id, index)">{{ __('common.delete') }}</button>
              </td>
            </tr>
          </tbody>
          <tbody v-else><tr><td colspan="9" class="border-0"><x-admin-no-data /></td></tr></tbody>
        </table>
      </div>

      <el-pagination v-if="coupons.data && coupons.data.length" layout="prev, pager, next" background
        :page-size="coupons.per_page" :current-page.sync="page" :total="coupons.total"></el-pagination>
    </div>

    <el-dialog :title="dialog.type === 'add' ? '{{ __('coupon.create') }}' : '{{ __('coupon.edit') }}'"
      :visible.sync="dialog.show" width="600px" @close="closeDialog('form')" :close-on-click-modal="false">

      <el-form ref="form" :rules="rules" :model="dialog.form" label-width="130px">

        <el-form-item label="{{ __('coupon.code') }}" prop="code">
          <el-input v-model="dialog.form.code" placeholder="例：SUMMER100" style="text-transform:uppercase"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('coupon.type') }}" prop="type">
          <el-select v-model="dialog.form.type" class="w-100">
            <el-option value="fixed" label="{{ __('coupon.type_fixed') }}"></el-option>
            <el-option value="percent" label="{{ __('coupon.type_percent') }}"></el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="{{ __('coupon.value') }}" prop="value">
          <el-input v-model="dialog.form.value" type="number" min="0">
            <template slot="append">@{{ dialog.form.type === 'fixed' ? 'TWD' : '%' }}</template>
          </el-input>
        </el-form-item>

        <el-form-item label="{{ __('coupon.min_order') }}">
          <el-input v-model="dialog.form.min_order" type="number" min="0" placeholder="0 = {{ __('coupon.no_limit') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('coupon.usage_limit') }}">
          <el-input v-model="dialog.form.usage_limit" type="number" min="0" placeholder="0 = {{ __('coupon.no_limit') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('coupon.usage_limit_per_user') }}">
          <el-input v-model="dialog.form.usage_limit_per_user" type="number" min="0" placeholder="0 = {{ __('coupon.no_limit') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('coupon.starts_at') }}">
          <el-date-picker v-model="dialog.form.starts_at" type="datetime" value-format="yyyy-MM-dd HH:mm:ss"
            placeholder="{{ __('coupon.no_limit') }}" class="w-100"></el-date-picker>
        </el-form-item>

        <el-form-item label="{{ __('coupon.expires_at') }}">
          <el-date-picker v-model="dialog.form.expires_at" type="datetime" value-format="yyyy-MM-dd HH:mm:ss"
            placeholder="{{ __('coupon.no_limit') }}" class="w-100"></el-date-picker>
        </el-form-item>

        <el-form-item label="{{ __('common.status') }}">
          <el-switch v-model="dialog.form.active" :active-value="1" :inactive-value="0"></el-switch>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="submit('form')">{{ __('common.save') }}</el-button>
          <el-button @click="dialog.show = false">{{ __('common.cancel') }}</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>
  </div>
@endsection

@push('footer')
<script>
  var app = new Vue({
    el: '#coupon-app',
    data: {
      coupons: @json($coupons ?? []),
      page: bk.getQueryString('page', 1) * 1,
      dialog: {
        show: false,
        type: 'add',
        index: null,
        form: {
          id: null,
          code: '',
          type: 'fixed',
          value: '',
          min_order: 0,
          usage_limit: 0,
          usage_limit_per_user: 1,
          starts_at: null,
          expires_at: null,
          active: 1,
        },
      },
      rules: {
        code:  [{ required: true, message: '請輸入優惠碼', trigger: 'blur' }],
        type:  [{ required: true, message: '請選擇折扣型別', trigger: 'change' }],
        value: [{ required: true, message: '請輸入折扣值', trigger: 'blur' }],
      },
    },

    watch: {
      page() { this.loadData(); },
    },

    methods: {
      loadData() {
        $http.get('coupons?page=' + this.page).then((res) => {
          this.coupons = res.data.coupons;
        });
      },

      openCreate() {
        this.dialog.type = 'add';
        this.dialog.index = null;
        this.dialog.form = { id: null, code: '', type: 'fixed', value: '', min_order: 0, usage_limit: 0, usage_limit_per_user: 1, starts_at: null, expires_at: null, active: 1 };
        this.dialog.show = true;
      },

      openEdit(index) {
        this.dialog.type = 'edit';
        this.dialog.index = index;
        let item = JSON.parse(JSON.stringify(this.coupons.data[index]));
        item.active = item.active ? 1 : 0;
        this.dialog.form = item;
        this.dialog.show = true;
      },

      submit(form) {
        this.$refs[form].validate((valid) => {
          if (!valid) {
            this.$message.error('{{ __('common.error_form') }}');
            return;
          }
          const isAdd = this.dialog.type === 'add';
          const method = isAdd ? 'post' : 'put';
          const url = isAdd ? 'coupons' : 'coupons/' + this.dialog.form.id;

          $http[method](url, this.dialog.form).then((res) => {
            this.$message.success(res.message);
            this.dialog.show = false;
            this.loadData();
          });
        });
      },

      deleteItem(id, index) {
        this.$confirm('{{ __('common.confirm_delete') }}', '{{ __('common.text_hint') }}', {
          confirmButtonText: '{{ __('common.confirm') }}',
          cancelButtonText: '{{ __('common.cancel') }}',
          type: 'warning',
        }).then(() => {
          $http.delete('coupons/' + id).then((res) => {
            this.$message.success(res.message);
            this.coupons.data.splice(index, 1);
          });
        }).catch(() => {});
      },

      closeDialog(form) {
        if (this.$refs[form]) this.$refs[form].resetFields();
      },
    },
  });
</script>
@endpush
