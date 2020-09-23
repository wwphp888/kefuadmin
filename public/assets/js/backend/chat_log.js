define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'chat_log/index' + location.search,
                    add_url: 'chat_log/add',
                    edit_url: 'chat_log/edit',
                    del_url: 'chat_log/del',
                    multi_url: 'chat_log/multi',
                    table: 'chat_log',
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
                        {field: 'from_id', title: __('From_id')},
                        {field: 'from_name', title: __('From_name')},
                        {field: 'from_avatar', title: __('From_avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'to_id', title: __('To_id')},
                        {field: 'to_name', title: __('To_name')},
                        {field: 'to_avatar', title: __('To_avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'merchant_id', title: __('Merchant_id')},
                        {field: 'send_status', title: __('Send_status'), searchList: {"1":__('Send_status 1'),"0":__('Send_status 0')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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