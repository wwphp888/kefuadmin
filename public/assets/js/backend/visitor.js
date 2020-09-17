define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'visitor/index' + location.search,
                    add_url: 'visitor/add',
                    edit_url: 'visitor/edit',
                    del_url: 'visitor/del',
                    multi_url: 'visitor/multi',
                    table: 'visitor',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'visitor_id', title: __('Visitor_id')},
                        {field: 'name', title: __('Name')},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'visitor_ip', title: __('Visitor_ip')},
                        {field: 'open_id', title: __('Open_id')},
                        {field: 'address', title: __('Address')},
                        {field: 'client_id', title: __('Client_id')},
                        {field: 'online_status', title: __('Online_status')},
                        {field: 'merchant_id', title: __('Merchant_id')},
                        {field: 'pre_kf_id', title: __('Pre_kf_id')},
                        {field: 'phone', title: __('Phone')},
                        {field: 'email', title: __('Email')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});