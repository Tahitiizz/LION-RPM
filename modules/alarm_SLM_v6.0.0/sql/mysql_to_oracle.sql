

-- migration des champs CLOB en champs varchar2(200)
-- comme je ne sais pas modifier le type d'une colonne CLOB avec Oracle, j'utilise la technique un peu violente consistant à
-- supprimer une colonne et la recréer avec le bon type. Ce n'est applicaque que si la table est vide de données.

-- table sys_alarm_email_sender
alter table sys_alarm_email_sender drop column time_aggregation;
alter table sys_alarm_email_sender add time_aggregation varchar2(200) NOT NULL;

alter table sys_alarm_email_sender drop column alarm_type;
alter table sys_alarm_email_sender add alarm_type varchar2(200) NOT NULL;


-- table sys_def_alarm_net_elts
alter table sys_def_alarm_net_elts drop column type_alarm;
alter table sys_def_alarm_net_elts add type_alarm varchar2(200) NOT NULL;

alter table sys_def_alarm_net_elts drop column lst_alarm_compute;
alter table sys_def_alarm_net_elts add lst_alarm_compute varchar2(200) NOT NULL;

alter table sys_def_alarm_net_elts drop column lst_alarm_interface;
alter table sys_def_alarm_net_elts add lst_alarm_interface varchar2(200) NOT NULL;


-- table sys_def_alarm_static
alter table sys_def_alarm_static drop column alarm_name;
alter table sys_def_alarm_static add alarm_name varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column alarm_trigger_data_field;
alter table sys_def_alarm_static add alarm_trigger_data_field varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column alarm_trigger_operand;
alter table sys_def_alarm_static add alarm_trigger_operand varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column alarm_trigger_type;
alter table sys_def_alarm_static add alarm_trigger_type varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column network;
alter table sys_def_alarm_static add network varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column time;
alter table sys_def_alarm_static add time varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column hn_value;
alter table sys_def_alarm_static add hn_value varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column family;
alter table sys_def_alarm_static add family varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column internal_id;
alter table sys_def_alarm_static add internal_id varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column client_type;
alter table sys_def_alarm_static add client_type varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column additional_field;
alter table sys_def_alarm_static add additional_field varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column additional_field_type;
alter table sys_def_alarm_static add additional_field_type varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column description;
alter table sys_def_alarm_static add description varchar2(200) NOT NULL;

alter table sys_def_alarm_static drop column critical_level;
alter table sys_def_alarm_static add critical_level varchar2(200) NOT NULL;


