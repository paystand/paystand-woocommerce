package internal

import com.kms.katalon.core.configuration.RunConfiguration
import com.kms.katalon.core.testobject.ObjectRepository as ObjectRepository
import com.kms.katalon.core.testdata.TestDataFactory as TestDataFactory
import com.kms.katalon.core.testcase.TestCaseFactory as TestCaseFactory
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase

/**
 * This class is generated automatically by Katalon Studio and should not be modified or deleted.
 */
public class GlobalVariable {
     
    /**
     * <p></p>
     */
    public static Object domain
     
    /**
     * <p></p>
     */
    public static Object user
     
    /**
     * <p></p>
     */
    public static Object password
     

    static {
        def allVariables = [:]        
        allVariables.put('default', [:])
        allVariables.put('Test paystand.ml', allVariables['default'] + ['domain' : 'http://localhost:8000/my-account/', 'user' : 'obaqueiro', 'password' : 'VdLB^T%#l$qs9RklY('])
        
        String profileName = RunConfiguration.getExecutionProfile()
        
        def selectedVariables = allVariables[profileName]
        domain = selectedVariables['domain']
        user = selectedVariables['user']
        password = selectedVariables['password']
        
    }
}
