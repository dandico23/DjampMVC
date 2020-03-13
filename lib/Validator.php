<?php

namespace lib;

class Validator
{

    /**
     * Constructor
     *
     * @param array $data - list with the values to be validated
     * @param array $rules - the rules to be applied to each list element
     *      The rules should be separated by |
     *      Example:
     *           $rules = [
     *              'email' => 'required|email',
     *              'games' => 'required|numeric',
     *              'expire_date' => 'after:tomorrow',
     *           ];
     *      Options:
     *          - required - field must exist in $data
     *          - email - field must be in mail format
     *          - numeric - field must be a number
     *          - accepted - The field under validation must be yes, on, 1, or true
     *          - active_url - The field under validation must have a valid 
     *                  A or AAAA record according to the dns_get_record PHP function.
     *          - after:date - The field under validation must be a value after a given date.
     *                    The dates will be passed into the strtotime PHP function
     *          - after_or_equal:date - Similar to after, but considering equal
     *          - before:date - Similar to previous 2 arguments
     *          - alpha - The field under validation must be entirely alphabetic characters.
     *          - alpha_numeric - The field under validation may have alpha-numeric characters
     *          - between:min,max - The field under validation must have a size between the given 
     *                    min and max. Strings and arrays are evaluated based in size
     *          - boolean - The field under validation must be able to be cast as a boolean. 
     *                      Accepted input are true, false, 1, 0, "1", and "0".
     *          - date - The field under validation must be a valid date according to
     *                   the strtotime PHP function
     *          - date_equals:date - The field under validation must be equal to the given date
     *                              The dates will be passed into the PHP strtotime function.
     *          - date_format:format - The field under validation must match the given format,
     *                                 function \DateTime::createFromFormat is used
     *          - different:field1,field2 - The field under validation must have a different
     *                                      value than given fields.
     * 
     * @return array $result - return if the data is valid, if negative case returns a message explaining why is invalid
     */
    public function validate($data, $rules)
    {
        $data = ['cdz' => 'e'];
        $rules = [
            'cdz' => 'different:a,b,c,d'
        ];

        foreach ($rules as $field => $field_rules) {

            if (isset($data[$field])) {
                $value = $data[$field];
            } else {
                $value = null;
            }
            
            $separeted_rules = explode("|", $field_rules);
            foreach ($separeted_rules as $rule) {
                $result = $this->validateField($field, $value, $rule);
                echo '<pre>' , var_dump($result) , '</pre>';
            }
            
        }

        return 1;
    }

    public function validateField($field, $value, $rule)
    {
        if ($rule == 'required' && !$this->validateRequired($value)) {

            $errMessage = "Field " . $field . " is required"; 
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'email' && !$this->validateEmail($value)) {

            $errMessage = "Invalid email format";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'numeric' && !$this->validateNumeric($value)) {

            $errMessage = "Field " . $field . " must be numeric";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'accepted' && !$this->validateAccepted($value)) {

            $errMessage = "Field " . $field . " must be yes, on, 1 or true";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'alpha' && !$this->validateAlphabetic($value)) {

            $errMessage = "Field " . $field . " must contain only alphabetical characters";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'alpha_numeric' && !$this->validateAlphaNumeric($value)) {

            $errMessage = "Field " . $field . " must contain only alphanumerical characters";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'array' && !$this->validateArray($value)) {

            $errMessage = "Field " . $field . " must be an array";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if ($rule == 'boolean' && !$this->validateBoolean($value)) {

            $errMessage = "Field " . $field . " must be a boolean";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if (strpos($rule, 'different') !== false) {

            if (!$this->validateDifferent($rule, $value)){
                $errMessage = "Field " . $field . " not different from specified fields";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        } else if (strpos($rule, 'date_equals') !== false) {

            $given_date = explode(":", $rule)[1];
            if ($given_date && !$this->validateDateEquals($value, $given_date)){
                $errMessage = "Field " . $field . " value not equal given date";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        } else if (strpos($rule, 'date_format') !== false) {

            $format = explode(":", $rule)[1];
            if ($format && !$this->validateDateFormat($value, $format)){
                $errMessage = "Field " . $field . " does not match given date format";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        }else if ($rule == 'date' && !$this->validateDate($value)) {

            $errMessage = "Field " . $field . " must be a date";
            return array("valid" => false, "invalid_message" => $errMessage);

        } else if (strpos($rule, 'after_or_equal') !== false) {

            $given_date = explode(":", $rule)[1];
            if ($given_date && !$this->validateAfterOrEqualDate($value, $given_date)){
                $errMessage = "Field " . $field . " value not after or equal given date";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        } else if (strpos($rule, 'after') !== false) {

            $given_date = explode(":", $rule)[1];
            if ($given_date && !$this->validateAfterDate($value, $given_date)){
                $errMessage = "Field " . $field . " value not after given date";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        } else if (strpos($rule, 'before_or_equal') !== false) {

            $given_date = explode(":", $rule)[1];
            if ($given_date && !$this->validateBeforeOrEqualDate($value, $given_date)){
                $errMessage = "Field " . $field . " value not before or equal given date";
                return array("valid" => false, "invalid_message" => $errMessage);
            }

        } else if (strpos($rule, 'before') !== false) {

            $given_date = explode(":", $rule)[1];
            if ($given_date && !$this->validateBeforeDate($value, $given_date)){
                $errMessage = "Field " . $field . " value not before given date";
                return array("valid" => false, "invalid_message" => $errMessage);
            }
        } else if (strpos($rule, 'between') !== false) {

            if (!$this->validateBetween($rule, $value)) {
                $errMessage = "Field " . $field . " not between given values";
                return array("valid" => false, "invalid_message" => $errMessage);
            }
        }

        return array("valid" => true, "invalid_message" => "");
    }

    public function validateDifferent($rule, $value)
    {
        $fields = explode(":", $rule)[1];
        $fields = explode(",",$fields);
        foreach ($fields as $field) {
            if ($field == $value) {
                return false;
            }
        }
        return true;
    }
    

    public function validateBetween($rule, $value)
    {
        $min_max = explode(":", $rule)[1];
        $split = explode(",", $min_max);
        $min = $split[0];
        $max = $split[1];
        return ( $value > $min && $value < $max);
    }

    public function validateBoolean($value)
    {
        return ($value === true || $value === false || $value === 1 
                || $value === 0 || $value === '1' || $value === '0');
    }

    public function validateDate($value)
    {
        return strtotime($value);
    }

    public function validateDateFormat($value, $format)
    {
        return \DateTime::createFromFormat($format, $value);
    }

    public function validateArray($value)
    {
        return is_array($value);
    }

    public function validateAlphaNumeric($value)
    {
        return ctype_alnum($value);
    }

    public function validateAlphabetic($value)
    {
        return ctype_alpha($value);
    }

    public function validateDateEquals($value, $given_date)
    {   
        $value = strtotime($value);
        $given_date = strtotime($given_date);
        return $value && $given_date && $value == $given_date;
    }

    public function validateAfterDate($value, $given_date)
    {
        $value = strtotime($value);
        $given_date = strtotime($given_date);
        return $value && $given_date && $value > $given_date;
    }

    public function validateAfterOrEqualDate($value, $given_date)
    {
        $value = strtotime($value);
        $given_date = strtotime($given_date);
        return $value && $given_date && $value >= $given_date;
    }

    public function validateBeforeDate($value, $given_date)
    {
        $value = strtotime($value);
        $given_date = strtotime($given_date);
        return $value && $given_date && $value < $given_date;
    }
    
    public function validateBeforeOrEqualDate($value, $given_date)
    {
        $value = strtotime($value);
        $given_date = strtotime($given_date);
        return $value && $given_date && $value <= $given_date;
    }
  
    public function validateAccepted($value)
    {
        return ($value == 'yes' || $value == 'on' || $value == 1 || $value === true);
    }

    public function validateNumeric($value)
    {
       return is_numeric($value);
    }

    public function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function validateRequired($value)
    {
        return $value;
    }

}