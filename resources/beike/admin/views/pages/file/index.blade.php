@extends('admin::layouts.master')

@section('content')
    <div id="app">
        <div class="card">
            <div class="card-body">
                <el-upload
                        class="upload-demo"
                        drag
                        :headers="headers"
                        action="{{ admin_route('files.store') }}"
                        multiple
                        with-credentials
                >
                    <i class="el-icon-upload"></i>
                    <div class="el-upload__text">將檔案拖到此處，或<em>點選上傳</em></div>
                    <div class="el-upload__tip" slot="tip">只能上傳jpg/png檔案，且不超過500kb</div>
                </el-upload>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
    new Vue({
      el: '#app',
      data: {
        files: [],
        headers: {
          'X-CSRF-TOKEN': @json(csrf_token())
        }
      },
      methods: {
      }
    })


    </script>
@endpush
