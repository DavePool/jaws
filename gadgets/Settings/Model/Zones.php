<?php
/**
 * Zones Model
 *
 * @category    GadgetModel
 * @package     Settings
 */
class Settings_Model_Zones extends Jaws_Gadget_Model
{
    /**
     * Get a list of the countries
     *
     * @access  public
     * @return  mixed   Array of countries or Jaws_Error on failure
     */
    function GetCountries()
    {
        return Jaws_ORM::getInstance()->table('zones')
            ->select('country:integer', 'title')
            ->where('province', 0)
            ->orderBy('country')
            ->fetchAll();
    }

    /**
     * Get a list of the provinces
     *
     * @access  public
     * @param   int     $country    Country code
     * @return  mixed   Array of provinces or Jaws_Error on failure
     */
    function GetProvinces($country = 0)
    {
        return Jaws_ORM::getInstance()->table('zones')
            ->select('province:integer', 'title')
            ->where('country', (int)$country)
            ->and()
            ->where('province', 0, '<>')
            ->and()
            ->where('city', 0)
            ->orderBy('province')
            ->fetchAll();
    }
    
    /**
     * Get a province
     *
     * @access  public
     * @param   int     $province   Province code
     * @return  mixed   Array of Provinces or Jaws_Error on failure
     */
    function GetProvince($province)
    {
        return Jaws_ORM::getInstance()->table('zones')
            ->select('country:integer', 'province:integer', 'title')
            ->where('location', (int)$province)
            ->fetchRow();
    }

    /**
     * Get a list of the cities by IDs
     *
     * @access  public
     * @param   int|array   $cities     Cities Id
     * @return  mixed       Array of Cities or Jaws_Error on failure
     */
    function GetCitiesByIDs($cities = array())
    {
        if (!is_array($cities)) {
            $cities = array($cities);
        }

        return Jaws_ORM::getInstance()
            ->table('zones')
            ->select('province:integer', 'city:integer', 'title')
            ->where('location', $cities, 'in')
            ->fetchAll();
    }

    /**
     * Get a list of the Cities
     *
     * @access  public
     * @param   int|array   $provinces  Provinces Id
     * @param   int         $country    Country code
     * @return  mixed       Array of Cities or Jaws_Error on failure
     */
    function GetCities($provinces = array(), $country = 0)
    {
        if (!is_array($provinces)) {
            $provinces = array($provinces);
        }

        return Jaws_ORM::getInstance()
            ->table('zones')
            ->select('province:integer', 'city:integer', 'title')
            ->where('country', (int)$country)
            ->and()
            ->where('province', $provinces, 'in')
            ->and()
            ->where('city', 0, '<>')
            ->orderBy('city')
            ->fetchAll();
    }

    /**
     * Get a city info
     *
     * @access  public
     * @param   int     $city       City code
     * @return mixed Array of Cities or Jaws_Error on failure
     */
    function GetCity($city)
    {
        return Jaws_ORM::getInstance()
            ->table('zones')
            ->select('country:integer', 'province:integer', 'city:integer', 'title')
            ->where('location', (int)$city)
            ->fetchRow();
    }

}