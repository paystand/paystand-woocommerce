import com.kms.katalon.core.main.TestCaseMain
import com.kms.katalon.core.logging.KeywordLogger
import groovy.lang.MissingPropertyException
import com.kms.katalon.core.testcase.TestCaseBinding
import com.kms.katalon.core.driver.internal.DriverCleanerCollector
import com.kms.katalon.core.model.FailureHandling
import com.kms.katalon.core.configuration.RunConfiguration
import com.kms.katalon.core.webui.contribution.WebUiDriverCleaner
import com.kms.katalon.core.mobile.contribution.MobileDriverCleaner


DriverCleanerCollector.getInstance().addDriverCleaner(new com.kms.katalon.core.webui.contribution.WebUiDriverCleaner())
DriverCleanerCollector.getInstance().addDriverCleaner(new com.kms.katalon.core.mobile.contribution.MobileDriverCleaner())


RunConfiguration.setExecutionSettingFile('/var/folders/z1/_3h4ccq537ndhjt65bptlm500000gn/T/Katalon/Test Cases/Add payment method/20180626_162308/execution.properties')

TestCaseMain.beforeStart()

        TestCaseMain.runTestCaseRawScript(
'''import static com.kms.katalon.core.checkpoint.CheckpointFactory.findCheckpoint
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject
import com.kms.katalon.core.checkpoint.Checkpoint as Checkpoint
import com.kms.katalon.core.checkpoint.CheckpointFactory as CheckpointFactory
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as MobileBuiltInKeywords
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as Mobile
import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.testcase.TestCase as TestCase
import com.kms.katalon.core.testcase.TestCaseFactory as TestCaseFactory
import com.kms.katalon.core.testdata.TestData as TestData
import com.kms.katalon.core.testdata.TestDataFactory as TestDataFactory
import com.kms.katalon.core.testobject.ObjectRepository as ObjectRepository
import com.kms.katalon.core.testobject.TestObject as TestObject
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WSBuiltInKeywords
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WS
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUiBuiltInKeywords
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import internal.GlobalVariable as GlobalVariable
import org.openqa.selenium.Keys as Keys

not_run: WebUI.openBrowser('')

not_run: WebUI.navigateToUrl('http://localhost:8000/?page_id=58')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_username'), 'aldo')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_password'), '123Qaz')

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/input_login'))

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Payment methods'))

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Add payment method'))

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/input_place_order'))

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_payer_email'), 'avaldez@paystand.com')

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Card'))

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_name'), 'ALDO')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_number'), '4242 4242 4242 4242')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_expiry'), '12 / 21')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_card_security_code'), '123')

not_run: WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Enter Billing Informati'))

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_street'), 'street')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_city'), 'guadalajara')

not_run: WebUI.setText(findTestObject('Page_My Account  MyWordpress/input_address_postal'), '62000')

WebUI.click(findTestObject('Page_My Account  MyWordpress/span_Alabama'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/button_Save  Card For Future P'))

WebUI.click(findTestObject('Page_My Account  MyWordpress/a_Logout'))

WebUI.closeBrowser()

''', 'Test Cases/Add payment method', new TestCaseBinding('Test Cases/Add payment method', [:]), FailureHandling.STOP_ON_FAILURE , false)
    
