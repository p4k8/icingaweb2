<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host comments
 */
class HostcommentQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'comments' => array(
            'comment_author'        => 'c.author_name COLLATE latin1_general_ci',
            'comment_author_name'   => 'c.author_name',
            'comment_data'          => 'c.comment_data',
            'comment_expiration'    => 'CASE c.expires WHEN 1 THEN UNIX_TIMESTAMP(c.expiration_time) ELSE NULL END',
            'comment_internal_id'   => 'c.internal_comment_id',
            'comment_is_persistent' => 'c.is_persistent',
            'comment_timestamp'     => 'UNIX_TIMESTAMP(c.comment_time)',
            'comment_type'          => "CASE c.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
            'host'                  => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'             => 'ho.name1',
            'object_type'           => '(\'host\')'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host_alias'            => 'h.alias',
            'host_display_name'     => 'h.display_name COLLATE latin1_general_ci'
        ),
        'hoststatus' => array(
            'host_state' => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service'                => 'so.name2 COLLATE latin1_general_ci',
            'service_description'    => 'so.name2',
            'service_display_name'   => 's.display_name COLLATE latin1_general_ci',
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('c' => $this->prefix . 'comments'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = c.object_id',
            array()
        )->where(
            'ho.is_active = ?',
            1
        )->where(
            'ho.objecttype_id = ?',
            1
        );
        $this->joinedVirtualTables['comments'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id',
            array()
        )->where(
            'hgo.is_active = ?',
            1
        )
        ->where(
            'hgo.objecttype_id = ?',
            3
        );
        $this->distinct();
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        );
        $this->distinct();
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1',
            array()
        )->where(
            'so.is_active = ?',
            1
        )
        ->where(
            'so.objecttype_id = ?',
            2
        );
        $this->distinct();
    }
}
